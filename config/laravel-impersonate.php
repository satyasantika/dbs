<?php

return [

    'session_key' => 'impersonated_by',

    'session_guard' => 'impersonator_guard',

    'session_guard_using' => 'impersonator_guard_using',

    'default_impersonator_guard' => 'web',

    /*
     * Redirect after impersonation based on the impersonated user's role
     * (see route name "home" -> /go-home).
     */
    'take_redirect_to' => 'home',

    'leave_redirect_to' => '/admin/users',

];
