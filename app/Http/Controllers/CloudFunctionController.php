<?php

/**
 * Manage cloud functions.
 *
 * Does not upload nor run these.
 */

namespace App\Http\Controllers;

use App\CloudFunction;
use App\CloudFunction\HandlerInterface;
use App\Jobs\CloudFunctionDeploy;
use Illuminate\Http\Request;

class CloudFunctionController extends Controller
{
    public function getValidations()
    {
        return [
            'display_name' => 'required|max:200',
            'code' => 'nullable|max:10000',
            'variables' => 'nullable|array',
            'active' => 'nullable|boolean',
            'type' => ['in:attribute,guard,jit,user_event'],
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->input('type')) {
            return CloudFunction::where('type', $request->input('type'))->get();
        } else {
            return CloudFunction::get();
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        return CloudFunction::create($this->withDefaults($request->validate($this->getValidations())));
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(CloudFunction $cloudFunction)
    {
        return $cloudFunction;
    }

    public function invoke(Request $request, CloudFunction $cloudFunction)
    {
        /** @var HandlerInterface */
        $handler = resolve(HandlerInterface::class);

        return $handler->invoke(
            CloudFunction::find($cloudFunction->id),
            $request->input()
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CloudFunction $cloudFunction)
    {
        $cloudFunction->forceFill($this->withDefaults($request->validate($this->getValidations())));
        $cloudFunction->save();

        CloudFunctionDeploy::dispatch($cloudFunction);

        return $cloudFunction;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(CloudFunction $cloudFunction)
    {
        $cloudFunction->delete();

        return response(null, 200);
    }

    public function withDefaults($data)
    {
        if (! isset($data['code'])) {
            $code = '';

            switch ($data['type']) {
                case 'jit':
                    $code = file_get_contents(resource_path('serverless/jit.default.js'));
                    break;
                case 'user_event':
                    $code = file_get_contents(resource_path('serverless/user_event.default.js'));
                    break;
                case 'attribute':
                    $code = file_get_contents(resource_path('serverless/attribute.default.js'));
                    break;
            }

            $data['code'] = $code;
        }

        return $data;
    }
}
