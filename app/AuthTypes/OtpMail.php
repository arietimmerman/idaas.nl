<?php

namespace App\AuthTypes;

use App\AuthChain\ModuleInterface;
use App\AuthChain\ModuleResult;
use App\AuthChain\State;
use App\AuthChain\Subject;
use App\EmailTemplate;
use App\Mail\StandardMail;
use App\Repository\SubjectRepository;
use App\Repository\UserRepository;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use ParagonIE\ConstantTime\Base32;

class OtpMail extends AbstractType
{
    public function isPassive()
    {
        return false;
    }

    public function init(Request $request, State $state, ModuleInterface $module)
    {
    }

    public function getDefaultName()
    {
        return 'One Time Password via email';
    }

    /**
     * This module can work as a first-factor, or as a second-factor in case the subject has a mail address
     */
    public function isEnabled(?Subject $subject)
    {
        return $subject == null || $subject->getEmail('email') != null;
    }

    public function process(Request $request, State $state, ModuleInterface $module)
    {
        if ($request->input('otp')) {
            if ($this->getOtp($state) == $request->input('otp')) {
                $user = User::find(decrypt($request->input('user_id_hashed')));

                $result = $module->baseResult()->setSubject(
                    resolve(SubjectRepository::class)
                        ->with(
                            $user->email,
                            $this,
                            $module
                        )->setTypeIdentifier($this->getIdentifier())->setUserId($user->id)
                )->complete()->setPrompted(true);

                if ($request->input('remember') !== true) {
                    $result->setRememberAlways(false);
                    $result->setRememberForSession(false);
                }

                return $result;
            } else {
                return $module->baseResult()->setCompleted(false)->setResponse(
                    response(
                        [
                            'error' => 'The provided otp is incorrect.',
                        ],
                        422
                    )
                );
            }
        } elseif ($state->getSubject() != null) {
            //MUST approve subject!
            $subject = $state->getSubject();

            if ($state->getSubject()->getEmail() == null) {
                return (new ModuleResult())
                    ->setCompleted(false)
                    ->setResponse(response(['error' => 'No email address is known for this user']));
            }

            if ($state->getSubject()->getUserId() == null) {
                return (new ModuleResult())
                    ->setCompleted(false)
                    ->setResponse(response(['error' => 'No user id is known for this user']));
            }

            $otp = $this->getOtp($state);

            Mail::to($state->getSubject()->getEmail())->send(
                new StandardMail(
                    @$module->config['template_id'],
                    [
                        'subject' => $state->getSubject(),
                        'user' => $state->getSubject() ? $state->getSubject()->getUser() : null,
                        'otp' => $otp,
                    ],
                    EmailTemplate::TYPE_ONE_TIME_PASSWORD,
                    $subject->getPreferredLanguage()
                )
            );

            return $module->baseResult()->setCompleted(false)->setResponse(
                response(
                    [
                        'user_id_hashed' => encrypt($state->getSubject()->getUserId()),
                        'seed' => '123', //TODO: generate an always unique OTP.
                    ]
                )
            );
        } else {
            $user = resolve(UserRepository::class)->findByIdentifier($request->input('username'));

            if ($user == null) {
                return (new ModuleResult())
                    ->setCompleted(false)
                    ->setResponse(response(['error' => 'We could not find a user with this attribute.'], 422));
            }

            $otp = $this->getOtp($state);

            Mail::to($user->email)->send(
                new StandardMail(
                    @$module->config['template_id'],
                    [
                        'otp' => $otp,
                        'subject' => $state->getSubject(),
                        'user' => $state->getSubject() ? $state->getSubject()->getUser() : null,
                    ],
                    EmailTemplate::TYPE_ONE_TIME_PASSWORD,
                    $user->preferredLanguage
                )
            );

            /**
             * Let the client store the state. However, let it not influence the user_id_hash
             */
            return $module->baseResult()->setCompleted(false)->setResponse(
                response(
                    [
                        'user_id_hashed' => encrypt($user->id),
                        'seed' => '123', //TODO: generate an always unique OTP.
                    ]
                )
            );
        }
    }

    public static function getOtp(State $state)
    {
        return substr(Base32::encodeUnpadded(encrypt($state->getstateId())), 0, 7);
    }
}
