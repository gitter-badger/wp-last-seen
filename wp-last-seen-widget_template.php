<?php

$iconActive = $this->WP_LastSeen->pluginImgURL('available.png');
$iconIdle = $this->WP_LastSeen->pluginImgURL('content.png');
$iconOffline = $this->WP_LastSeen->pluginImgURL('away.png');

/* Initialization. Keep track of what we are going to use */
$userTimestamp = ''; // the last action the logged in user has made (recorded in the DB)
$activeTimePassed = ''; // holds a human readable time format (days, hours, minutes, seconds)
$iconToUse = ''; // so which icon should we use?
$html = ''; // output a single variable

$html .= (isset($instance['title_text'])) ? '<h5>' . $instance['title_text'] . '</h5>' : '';
$html .= "<ul class='simple'>";

//    [ID] => 1
//    [user_login] =>
//    [user_pass] => $P$BZSikBBhr4RFQhKSiHwBodAffr7UoT.
//    [user_nicename] =>
//    [user_email] => dora@mysuper.site
//    [user_url] =>
//    [user_registered] => 2015-06-22 11:30:14
//    [user_activation_key] =>
//    [user_status] => 0
//    [display_name] => Dora Zaitev

foreach ($this->WP_LastSeen->getAllUsers($this->WP_LastSeen->getAllUsersRaw()) as $user) {

    // Get the user timestamp
    $userTimestamp = get_user_meta($user->ID, $this->WP_LastSeen->getClassName());
    $userTimestamp = $userTimestamp[0]; // PHP 5.3 please die

    // time in seconds passed from last action
    $timePassed = $userTimestamp - time();

    // convert to a human readable format
//    Array
//    (
//    [day(s)] => 1
//    [hours] => 0
//    [minutes] => 32
//    [seconds] => 33
//    )
    $timePassed_humanReadable = $this->WP_LastSeen->secondsToTime($timePassed);

    // Clean the array of 0 values, eg 1 day(s) 32 minutes 33 seconds
    // Use proper plural, if we have 1 day, don't show day(s)
    foreach ($timePassed_humanReadable as $key => $value) {
        $activeTimePassed_numerical = (!empty($value)) ? $value . " {$key}" : ''; // expel any zero values
        $activeTimePassed_human = (($value != 1) && !empty($value)) ? 's ' : ' '; // if value != 1, use the plural
        $activeTimePassed .= $activeTimePassed_numerical . $activeTimePassed_human;
    }

    // Show a proper status icon based on time passed
    if (time() < strtotime("+3 minutes", $userTimestamp)) {
        $iconToUse = $iconActive;
        $suffix = 'active';
    } elseif (time() < strtotime("+5 minutes", $userTimestamp)) {
        $iconToUse = $iconIdle;
        $suffix = 'idle';
    } else {
        $iconToUse = $iconOffline;
        $suffix = 'offline';
    }

    $html .= "
        <li>
            <!-- icon -->
            <img src='{$iconToUse}' class='iconToUse' />
            <!-- username -->
            {$user->user_nicename} -
            <!-- timestamp: last active -->
            {$activeTimePassed} ({$suffix})
        </li>
    ";

}


$html .= "</ul>";
echo $html;