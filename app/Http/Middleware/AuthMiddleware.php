<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Classes\Auth\Auth;
use Throwable;

class AuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // dd($request->all());
        try {
            $authHeader = $request->getHeaderLine('Authorization');
            $token = trim(explode(' ', $authHeader)[1]); // skips Bearer string
            $auth = new Auth;
            //decode token
            $tkn_decode = $auth->decodeToken($token);
            $user = (array) $tkn_decode;
        } catch (Throwable $e) {
            $error = $e->getMessage();
            $payload = [
                'success' => false,
                'message' => "Failed to authenticate. Authorization header token is missing, expired or invalid.",
                'error' => $error,
                'server_time' => date('Y-m-d H:i:s')
            ];
            $status = 401;
        }

        if ($status != 200) {
            //authorization error occured
            return response()->json(['payload' => $payload]);
                // ->withHeader('Content-Type', 'application/json')
                // ->withStatus($status);
        } else {
            //$next the request to proceed to the route
            $request = $request->withAttribute('user', json_encode($user));
            $response = $next($request);

            return $response;
        }
        return $next($request);
    }
}
