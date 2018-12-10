<?php

namespace TsfCorp\Email\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use TsfCorp\Email\Models\EmailBounce;
use TsfCorp\Email\Models\EmailModel;

class MailgunWebhookController
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        info($request->all());
        $rules = array(
			'event-data.message.headers.message-id' => 'required',
			'signature.signature'       => 'required',
			'signature.timestamp'       => 'required',
			'signature.token'           => 'required',
		);

		$validator = Validator::make($request->all(), $rules);

		if ($validator->fails())
            return response()->json('Validation error.', 403);

		if ( ! $this->hasAccess($request))
			return response()->json('Invalid signature.', 401);

		$identifier = $request->input('event-data.message.headers.message-id');

        $email = EmailModel::getByRemoteIdentifier("<{$identifier}>");

		if ( ! $email)
			return response()->json('Record not found.', 406);

		if ($request->input('event-data.event') == 'failed' && $request->input('event-data.severity') == 'permanent')
        {
            $email->bounces_count++;
		    $email->save();

            $bounce = new EmailBounce();
            $bounce->email_id = $email->id;
            $bounce->recipient = $request->input('event-data.recipient');
            $bounce->code = $request->input('event-data.delivery-status.code');
            $bounce->reason = $request->input('event-data.reason');
            $bounce->description = $request->input('event-data.delivery-status.description');
            $bounce->message = $request->input('event-data.delivery-status.message');
            $bounce->save();
        }

		return response()->json('Thank you.', 200);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return bool
     */
    private function hasAccess(Request $request)
	{
		if (time() - $request->input('signature.timestamp') > 15)
        	return false;

		$webhook_hash = hash_hmac('SHA256', $request->input('signature.timestamp').$request->input('signature.token'), config('email.providers.mailgun.api_key'));

		return $webhook_hash == $request->input('signature.signature');
	}
}