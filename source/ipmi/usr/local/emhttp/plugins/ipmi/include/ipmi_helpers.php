<?php
/* get ipmi config and network options */
require_once '/usr/local/emhttp/plugins/ipmi/include/ipmi_options.php';

/* get an array of all sensors and their values */
function ipmi_sensors($options=null) {
	$cmd= "/usr/sbin/ipmi-sensors --output-sensor-thresholds --comma-separated-output --output-sensor-state --ignore-not-available-sensors --non-abbreviated-units --no-header-output --interpret-oem-data $options 2>/dev/null";
	exec($cmd, $output, $return);

	if ($return)
		return []; // return empty array if error

	// key names for ipmi sensors output
	$keys = ['ID','Name','Type','State','Reading','Units','LowerNR','LowerC','LowerNC','UpperNC','UpperC','UpperNR','Event'];
	$sensors = [];

	foreach($output as $line){

		$sensor_raw = explode(",", $line);
		$size_raw = sizeof($sensor_raw);

		// add sensor keys as keys to ipmi sensor output
		$sensor = ($size_raw < 13) ? []: array_combine($keys, array_slice($sensor_raw,0,13,true));
		  /*array_combine(array_slice($keys,0,$size_raw,true), $sensor_raw)*/

		if (empty($options)){
			$sensors[$sensor['ID']] = $sensor;
		}else{

			//split id into host and id
			$id = explode(':',$sensor['ID']);
			$sensor['IP'] = trim($id[0]);
			$sensor['ID'] = trim($id[1]);
			if ($sensor['IP'] == 'localhost')
				$sensor['IP'] = '127.0.0.1';

			// add sensor to array of sensors
			$sensors[ip2long($sensor['IP']).'_'.$sensor['ID']] = $sensor;
	}
}
	return $sensors;
}

/* get array of events and their values */
function ipmi_events($options=null){
	$cmd = "/usr/sbin/ipmi-sel --comma-separated-output --output-event-state --no-header-output --interpret-oem-data $options 2>/dev/null";
	exec($cmd, $output, $return); 

	//if ($return)
		//return []; // return empty array if error

	// key names for ipmi event output
	$keys = ['ID','DATE','Time','Name','Type','State','Event'];
	$events = [];

	foreach($output as $line){

		$event_raw = explode(",", $line);
		$size_raw = sizeof($event_raw);

		// add event keys as keys to ipmi event output
		$event = ($size_raw < 7) ? []: array_combine($keys, array_slice($event_raw,0,7,true));

		if (empty($options)){
			$events[$event['ID']] = $event;
		}else{

		//split id into host and id
		$id = explode(':',$event['ID']);
		$event['IP'] = trim($id[0]);
		$event['ID'] = trim($id[1]);
		if ($event['IP'] == 'localhost')
			$event['IP'] = '127.0.0.1';

		// add event to array of events
		$events[ip2long($event['IP']).'_'.$event['ID']] = $event;
	}
}
	return $events;
}

/* get select options for a given sensor type */
function ipmi_get_options($sensors, $type, $selected=null, $hdd=null){
	if ($hdd)
		// add hard drive temp as option
		$sensors[] = ['IP' => '', 'ID' => 'HDD', 'Name' => 'HDD Temperature', 'Type' => 'Temperature', 'State' => 'Nominal'];

	$options = "";
	foreach($sensors as $id => $sensor){
		if ($sensor["Type"] == $type && $sensor["State"] != "N/A"){
			$name = $sensor['Name'];
			$ip = (empty($sensor['IP'])) ? '' : " (${sensor['IP']})";
			$options .= "<option value='$id'";

			// set saved option as selected
			if ($selected == $id)
				$options .= " selected";

		$options .= ">$name$ip</option>";
		}
	}
	return $options;
}

// get options for high or low temp thresholds
function temp_get_options($range, $selected=null){
	$temps = [20,25,30,35,40,45,50,55,60,65,70,75,80];
	if ($range == 'HIGH')
	  rsort($temps);
 $options = "";
 foreach($temps as $temp){
			$options .= "<option value='$temp'";

			// set saved option as selected
			if ($selected == $temp)
				$options .= " selected";

		$options .= ">$temp</option>";

 	}
 	return $options;
	}

/* get reading for a given sensor by name */
function ipmi_get_readings($options=null) {
	$cmd = "/usr/sbin/ipmi-sensors --comma-separated-output --ignore-not-available-sensors --no-header-output --no-sensor-type-output --interpret-oem-data $options 2>/dev/null";
	exec($cmd, $output, $return);

	if ($return)
		return []; // return empty array if error

	// key names for ipmi sensors output
	$keys = ['ID', 'Name', 'Reading', 'Units', 'Event'];
	$sensors = [];

	foreach($output as $line){

		// add sensor keys as keys to ipmi sensor output
		$sensor_raw = explode(",", $line);
		$size_raw = sizeof($sensor_raw);
		$sensor = ($size_raw < 5) ? []: array_combine($keys, array_slice($sensor_raw,0,5,true));

		if (empty($options)){
			$sensors[$sensor['ID']] = $sensor;
		}else{
			//split id into host and id
			$id = explode(':',$sensor['ID']);
			$sensor['IP'] = trim($id[0]);
			$sensor['ID'] = trim($id[1]);
			if ($sensor['IP'] == 'localhost')
				$sensor['IP'] = '127.0.0.1';

			// add sensor to array of sensors
			$sensors[ip2long($sensor['IP']).'-'.$sensor['ID']] = $sensor;
	}
}
	return $sensors; // sensor readings
}

function ipmi_get_fans($sensors){
	foreach($sensors as $key => $sensor){
		if ($sensor['Type'] == 'Fan' && $sensor['State'] != 'N/A')
			$fans[] = $key; 
	}
	return $fans;
}
?>