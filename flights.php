<?php
ob_start( 'ob_gzhandler' );

/*

Written on 20180210. I'm very proud of this code. A lot of math went into getting the flight paths and the planes pointing in the right direction. API details here https://flightaware.com/commercial/flightxml/explorer/

There are four statuses of a flight:

-Departed
-Departing (Scheduled)
-Arrived
-Arriving (Enroute)

*/

require_once('cnct.php');

$options = array(
                 'trace' => true,
                 'exceptions' => 0,
                 'login' => 'girishgupta',
                 'password' => '********',
                 );
$client = new SoapClient('http://flightxml.flightaware.com/soap/FlightXML2/wsdl', $options);

//$params = array("max_size" => "10");
//$client->SetMaximumResultSize($params);

$airport = stripslashes($_GET['airport']); // Get the main airport name

$data = mysqli_query($link, "SELECT * FROM fl_airports WHERE id = '".$airport."'"); // Check if the airport already in my table fl_airports
if (mysqli_num_rows($data)==0) // If not...
{
    $params = array("airportCode" => $airport); // Get from API
    $airport_info_ = $client->AirportInfo($params);

    $airport_info['id'] = $airport; // Set variables
    $airport_info['lat'] = $airport_info_->AirportInfoResult->latitude;
    $airport_info['lon'] = $airport_info_->AirportInfoResult->longitude;
    $airport_info['location'] = $airport_info_->AirportInfoResult->location;
    $airport_info['name'] = $airport_info_->AirportInfoResult->name;
    $airport_info['timezone'] = $airport_info_->AirportInfoResult->timezone;

    mysqli_query($link, "INSERT INTO fl_airports (id, lat, lon, location, name, timezone) VALUES ('".$airport."', '".$airport_info['lat']."', '".$airport_info['lon']."', '".addslashes($airport_info['location'])."', '".addslashes($airport_info['name'])."', '".$airport_info['timezone']."')"); // Put into my table
}
else // If so...
{
    $airport_info = mysqli_fetch_array($data); // Set variables
}

date_default_timezone_set(str_replace(":", "", $airport_info['timezone'])); // Set the timezone for everything to the timezone for the main airport





$params = array("airport" => $airport_info['id'], "howMany" => "100", "filter" => "airline", "offset" => "0"); // Get a list of all the flights that have arrived
$routes = $client->Arrived($params);

$i=0;
foreach($routes->ArrivedResult->arrivals as $xx) // Go through each one and...
{
    $arrived_codes[$i] = $routes->ArrivedResult->arrivals[$i]->origin; // Set variables
        $airport = $arrived_codes[$i]; // Get the airport name
        $data = mysqli_query($link, "SELECT * FROM fl_airports WHERE id = '".$airport."'"); // Check if it's in my fl_airports table
        if (mysqli_num_rows($data)==0) //If not...
        {
            $params = array("airportCode" => $airport);
            $airport_info_ = $client->AirportInfo($params);

            $arrived_ids[$i] = $airport;
            $arrived_latitudes[$i] = $airport_info_->AirportInfoResult->latitude;
            $arrived_longitudes[$i] = $airport_info_->AirportInfoResult->longitude;
            $arrived_locations[$i] = $airport_info_->AirportInfoResult->location;
            $arrived_names[$i] = $airport_info_->AirportInfoResult->name;
            $arrived_timezones[$i] = $airport_info_->AirportInfoResult->timezone;

            mysqli_query($link, "INSERT INTO fl_airports (id, lat, lon, location, name, timezone) VALUES ('".$airport."', '".$arrived_latitudes[$i]."', '".$arrived_longitudes[$i]."', '".addslashes($arrived_locations[$i])."', '".addslashes($arrived_names[$i])."', '".$arrived_timezones[$i]."')");
        }
        else // If so...
        {
            $airport_info_ = mysqli_fetch_array($data);

            $arrived_ids[$i] = $airport;
            $arrived_latitudes[$i] = $airport_info_['lat'];
            $arrived_longitudes[$i] = $airport_info_['lon'];
            $arrived_locations[$i] = $airport_info_['location'];
            $arrived_names[$i] = $airport_info_['name'];
            $arrived_timezones[$i] = $airport_info_['timezone'];
        }
    $arrived_idents[$i] = $routes->ArrivedResult->arrivals[$i]->ident;
        $airline = $arrived_idents[$i][0].$arrived_idents[$i][1].$arrived_idents[$i][2]; // Get the airline name from the flight number
        $data = mysqli_query($link, "SELECT * FROM fl_airlines WHERE id = '".$airline."'"); // Check if it's in my fl_airlines table
        if (mysqli_num_rows($data)==0) // If not...
        {
            $params = array("airlineCode" => $airline); // Get from API
            $airline_info_ = $client->AirlineInfo($params);

            $arrived_airlines[$i] = $airline_info_->AirlineInfoResult->shortname; // Set variable of airline name

            mysqli_query($link, "INSERT INTO fl_airlines (id, name) VALUES ('".$airline."', '".addslashes($arrived_airlines[$i])."')"); // Add to my table
        }
        else // If so...
        {
            $airline_info_ = mysqli_fetch_array($data); // Set variable

            $arrived_airlines[$i] = $airline_info_['name'];
        }
    $arrived_times[$i] = $routes->ArrivedResult->arrivals[$i]->actualarrivaltime; // Set more variables
    $i++;
}

$arrived_codes = array_reverse($arrived_codes); // Reverse the order of the arrays holding details of the arrived planes so they appear correctly in table
$arrived_idents = array_reverse($arrived_idents);
$arrived_airlines = array_reverse($arrived_airlines);
$arrived_times = array_reverse($arrived_times);
$arrived_ids = array_reverse($arrived_ids);
$arrived_latitudes = array_reverse($arrived_latitudes);
$arrived_longitudes = array_reverse($arrived_longitudes);
$arrived_locations = array_reverse($arrived_locations);
$arrived_names = array_reverse($arrived_names);
$arrived_timezones = array_reverse($arrived_timezones);




$params = array("airport" => $airport_info['id'], "howMany" => "100", "filter" => "airline", "offset" => "0"); // See what planes are en route to main airport
$routes = $client->Enroute($params);

$i=0;
foreach($routes->EnrouteResult->enroute as $xx) // Go through them.
{
    $enroute_codes[$i] = $routes->EnrouteResult->enroute[$i]->origin; // Add variables
        $airport = $enroute_codes[$i]; // Get origin airport
        $data = mysqli_query($link, "SELECT * FROM fl_airports WHERE id = '".$airport."'"); // See if origin airport in my table
        if (mysqli_num_rows($data)==0) //If not...
        {
            $params = array("airportCode" => $airport);
            $airport_info_ = $client->AirportInfo($params);

            $enroute_ids[$i] = $airport;
            $enroute_latitudes[$i] = $airport_info_->AirportInfoResult->latitude;
            $enroute_longitudes[$i] = $airport_info_->AirportInfoResult->longitude;
            $enroute_locations[$i] = $airport_info_->AirportInfoResult->location;
            $enroute_names[$i] = $airport_info_->AirportInfoResult->name;
            $enroute_timezones[$i] = $airport_info_->AirportInfoResult->timezone;

            mysqli_query($link, "INSERT INTO fl_airports (id, lat, lon, location, name, timezone) VALUES ('".$airport."', '".$enroute_latitudes[$i]."', '".$enroute_longitudes[$i]."', '".addslashes($enroute_locations[$i])."', '".addslashes($enroute_names[$i])."', '".$enroute_timezones[$i]."')");
        }
        else //If so...
        {
            $airport_info_ = mysqli_fetch_array($data);

            $enroute_ids[$i] = $airport;
            $enroute_latitudes[$i] = $airport_info_['lat'];
            $enroute_longitudes[$i] = $airport_info_['lon'];
            $enroute_locations[$i] = $airport_info_['location'];
            $enroute_names[$i] = $airport_info_['name'];
            $enroute_timezones[$i] = $airport_info_['timezone'];
        }
    $enroute_idents[$i] = $routes->EnrouteResult->enroute[$i]->ident;
        $airline = $enroute_idents[$i][0].$enroute_idents[$i][1].$enroute_idents[$i][2]; // Same with airline
        $data = mysqli_query($link, "SELECT * FROM fl_airlines WHERE id = '".$airline."'");
        if (mysqli_num_rows($data)==0)
        {
            $params = array("airlineCode" => $airline);
            $airline_info_ = $client->AirlineInfo($params);

            $enroute_airlines[$i] = $airline_info_->AirlineInfoResult->shortname;

            mysqli_query($link, "INSERT INTO fl_airlines (id, name) VALUES ('".$airline."', '".addslashes($enroute_airlines[$i])."')");
        }
        else
        {
            $airline_info_ = mysqli_fetch_array($data);

            $enroute_airlines[$i] = $airline_info_['name'];
        }
    $enroute_times[$i] = $routes->EnrouteResult->enroute[$i]->estimatedarrivaltime;
    $enroute_filedtimes[$i] = $routes->EnrouteResult->enroute[$i]->filed_departuretime;


    $params_ = array("ident" => $enroute_idents[$i]); // Get in-flight information such as the location
    $routes_ = $client->InFlightInfo($params_);

    $enroute_latitudes_inair[$i] = $routes_->InFlightInfoResult->latitude;
    $enroute_longitudes_inair[$i] = $routes_->InFlightInfoResult->longitude;
    $enroute_altitudes_inair[$i] = $routes_->InFlightInfoResult->altitude;
    $enroute_groundspeeds_inair[$i] = $routes_->InFlightInfoResult->groundspeed;
    $enroute_headings_inair[$i] = $routes_->InFlightInfoResult->heading;
    $enroute_waypoints_inair[$i] = $routes_->InFlightInfoResult->waypoints;
        $enroute_waypoints_inair[$i] = explode(" ", $enroute_waypoints_inair[$i]);

    $i++;
}



$params = array("airport" => $airport_info['id'], "howMany" => "100", "filter" => "airline", "offset" => "0"); // Get departed flights from main airport
$routes = $client->Departed($params);

$i=0;
foreach($routes->DepartedResult->departures as $xx)
{
    $departed_codes[$i] = $routes->DepartedResult->departures[$i]->destination;
        $airport = $departed_codes[$i];
        $data = mysqli_query($link, "SELECT * FROM fl_airports WHERE id = '".$airport."'");
        if (mysqli_num_rows($data)==0)
        {
            $params = array("airportCode" => $airport);
            $airport_info_ = $client->AirportInfo($params);

            $departed_ids[$i] = $airport;
            $departed_latitudes[$i] = $airport_info_->AirportInfoResult->latitude;
            $departed_longitudes[$i] = $airport_info_->AirportInfoResult->longitude;
            $departed_locations[$i] = $airport_info_->AirportInfoResult->location;
            $departed_names[$i] = $airport_info_->AirportInfoResult->name;
            $departed_timezones[$i] = $airport_info_->AirportInfoResult->timezone;

            mysqli_query($link, "INSERT INTO fl_airports (id, lat, lon, location, name, timezone) VALUES ('".$airport."', '".$departed_latitudes[$i]."', '".$departed_longitudes[$i]."', '".addslashes($departed_locations[$i])."', '".addslashes($departed_names[$i])."', '".$departed_timezones[$i]."')");
        }
        else
        {
            $airport_info_ = mysqli_fetch_array($data);

            $departed_ids[$i] = $airport;
            $departed_latitudes[$i] = $airport_info_['lat'];
            $departed_longitudes[$i] = $airport_info_['lon'];
            $departed_locations[$i] = $airport_info_['location'];
            $departed_names[$i] = $airport_info_['name'];
            $departed_timezones[$i] = $airport_info_['timezone'];
        }
    $departed_idents[$i] = $routes->DepartedResult->departures[$i]->ident;
        $airline = $departed_idents[$i][0].$departed_idents[$i][1].$departed_idents[$i][2];
        $data = mysqli_query($link, "SELECT * FROM fl_airlines WHERE id = '".$airline."'");
        if (mysqli_num_rows($data)==0)
        {
            $params = array("airlineCode" => $airline);
            $airline_info_ = $client->AirlineInfo($params);

            $departed_airlines[$i] = $airline_info_->AirlineInfoResult->shortname;

            mysqli_query($link, "INSERT INTO fl_airlines (id, name) VALUES ('".$airline."', '".addslashes($departed_airlines[$i])."')");
        }
        else
        {
            $airline_info_ = mysqli_fetch_array($data);

            $departed_airlines[$i] = $airline_info_['name'];
        }
    $departed_times[$i] = $routes->DepartedResult->departures[$i]->actualdeparturetime;
    $departed_arrtimes[$i] = $routes->DepartedResult->departures[$i]->estimatedarrivaltime;

    $params_ = array("ident" => $departed_idents[$i]);
    $routes_ = $client->InFlightInfo($params_);

    $departed_latitudes_inair[$i] = $routes_->InFlightInfoResult->latitude;
    $departed_longitudes_inair[$i] = $routes_->InFlightInfoResult->longitude;
    $departed_altitudes_inair[$i] = $routes_->InFlightInfoResult->altitude;
    $departed_groundspeeds_inair[$i] = $routes_->InFlightInfoResult->groundspeed;
    $departed_headings_inair[$i] = $routes_->InFlightInfoResult->heading;
    $departed_waypoints_inair[$i] = $routes_->InFlightInfoResult->waypoints;
        $departed_waypoints_inair[$i] = explode(" ", $departed_waypoints_inair[$i]);

    $i++;
}

$departed_codes = array_reverse($departed_codes);
$departed_times = array_reverse($departed_times);
$departed_arrtimes = array_reverse($departed_times);

$departed_ids[$i] = array_reverse($departed_ids);
$departed_latitudes[$i] = array_reverse($departed_latitudes);
$departed_longitudes[$i] = array_reverse($departed_longitudes);
$departed_locations[$i] = array_reverse($departed_locations);
$departed_names[$i] = array_reverse($departed_names);
$departed_timezones[$i] = array_reverse($departed_timezones);

$departed_latitudes_inair[$i] = array_reverse($departed_latitudes_inair);
$departed_longitudes_inair[$i] = array_reverse($departed_longitudes_inair);
$departed_altitudes_inair[$i] = array_reverse($departed_altitudes_inair);
$departed_groundspeeds_inair[$i] = array_reverse($departed_groundspeeds_inair);
$departed_headings_inair[$i] = array_reverse($departed_headings_inair);
$departed_waypoints_inair[$i] = array_reverse($departed_waypoints_inair);


$params = array("airport" => $airport_info['id'], "howMany" => "100", "filter" => "airline", "offset" => "0");
$routes = $client->Scheduled($params);

$i=0;
foreach($routes->ScheduledResult->scheduled as $result)
{
    $departure_codes[$i] = $routes->ScheduledResult->scheduled[$i]->destination;
        $airport = $departure_codes[$i];
        $data = mysqli_query($link, "SELECT * FROM fl_airports WHERE id = '".$airport."'");
        if (mysqli_num_rows($data)==0)
        {
            $params = array("airportCode" => $airport);
            $airport_info_ = $client->AirportInfo($params);

            $departure_ids[$i] = $airport;
            $departure_latitudes[$i] = $airport_info_->AirportInfoResult->latitude;
            $departure_longitudes[$i] = $airport_info_->AirportInfoResult->longitude;
            $departure_locations[$i] = $airport_info_->AirportInfoResult->location;
            $departure_names[$i] = $airport_info_->AirportInfoResult->name;
            $departure_timezones[$i] = $airport_info_->AirportInfoResult->timezone;

            mysqli_query($link, "INSERT INTO fl_airports (id, lat, lon, location, name, timezone) VALUES ('".$airport."', '".$departure_latitudes[$i]."', '".$departure_longitudes[$i]."', '".addslashes($departure_locations[$i])."', '".addslashes($departure_names[$i])."', '".$departure_timezones[$i]."')");
        }
        else
        {
            $airport_info_ = mysqli_fetch_array($data);

            $departure_ids[$i] = $airport;
            $departure_latitudes[$i] = $airport_info_['lat'];
            $departure_longitudes[$i] = $airport_info_['lon'];
            $departure_locations[$i] = $airport_info_['location'];
            $departure_names[$i] = $airport_info_['name'];
            $departure_timezones[$i] = $airport_info_['timezone'];
        }
    $departure_idents[$i] = $routes->ScheduledResult->scheduled[$i]->ident;
        $airline = $departure_idents[$i][0].$departure_idents[$i][1].$departure_idents[$i][2];
        $data = mysqli_query($link, "SELECT * FROM fl_airlines WHERE id = '".$airline."'");
        if (mysqli_num_rows($data)==0)
        {
            $params = array("airlineCode" => $airline);
            $airline_info_ = $client->AirlineInfo($params);

            $departure_airlines[$i] = $airline_info_->AirlineInfoResult->shortname;

            mysqli_query($link, "INSERT INTO fl_airlines (id, name) VALUES ('".$airline."', '".addslashes($departure_airlines[$i])."')");
        }
        else
        {
            $airline_info_ = mysqli_fetch_array($data);

            $departure_airlines[$i] = $airline_info_['name'];
        }
    $departure_times[$i] = $routes->ScheduledResult->scheduled[$i]->filed_departuretime;
    $i++;
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Flight Map for <?php echo $airport_info['name']; ?></title>

<link rel="stylesheet" href="ammap/ammap.css" type="text/css">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
<script src="lib/ammap/ammap.js" type="text/javascript"></script>
<script src="lib/ammap/maps/js/worldLow.js" type="text/javascript"></script>

<script>
    var map;

    var targetSVG = "M9,0C4.029,0,0,4.029,0,9s4.029,9,9,9s9-4.029,9-9S13.971,0,9,0z M9,15.93 c-3.83,0-6.93-3.1-6.93-6.93S5.17,2.07,9,2.07s6.93,3.1,6.93,6.93S12.83,15.93,9,15.93 M12.5,9c0,1.933-1.567,3.5-3.5,3.5S5.5,10.933,5.5,9S7.067,5.5,9,5.5 S12.5,7.067,12.5,9z";
    var planeSVG = "m2,106h28l24,30h72l-44,-133h35l80,132h98c21,0 21,34 0,34l-98,0 -80,134h-35l43,-133h-71l-24,30h-28l15,-47";
    var starSVG = "M20,7.244 L12.809,6.627 L10,0 L7.191,6.627 L0,7.244 L5.455,11.971 L3.82,19 L10,15.272 L16.18,19 L14.545,11.971 L20,7.244 L20,7.244 Z M10,13.396 L6.237,15.666 L7.233,11.385 L3.91,8.507 L8.29,8.131 L10,4.095 L11.71,8.131 L16.09,8.507 L12.768,11.385 L13.764,15.666 L10,13.396 L10,13.396 Z";

    AmCharts.ready(function()
    {
        map = new AmCharts.AmMap();

        var dataProvider = { mapVar: AmCharts.maps.worldLow };

        map.areasSettings = { unlistedAreasColor: "grey" };

        map.imagesSettings =
        {
            color: "#FF6633",
            rollOverColor: "#FF6633",
            selectedColor: "#FF6633",
            pauseDuration: 0.2,
            animationDuration:2.5,
            adjustAnimationSpeed:true
        };

        map.linesSettings =
        {
            color: "#585869",
            alpha: 0.4
        };

        var lines =
        [

        <?php

            $i=0;
            foreach($departure_codes as $xx)
            {

        ?>

                {
                    id: "line<?php echo $i; ?>",
                    arc: -0.85,
                    alpha: 0.3,
                    title: "<?php echo $departure_airlines[$i]; ?> <?php echo $departure_idents[$i]; ?> going to <?php echo $departure_locations[$i]; ?> (<?php echo $departure_names[$i]; ?>), leaving at <?php echo date("H:i", $departure_times[$i]); ?>.",
                    latitudes: [<?php echo $airport_info['lat']; ?>, <?php echo $departure_latitudes[$i]; ?>],
                    longitudes: [<?php echo $airport_info['lon']; ?>, <?php echo $departure_longitudes[$i]; ?>]
                },

            <?php
                $i++;
            }
            ?>

        <?php

            //$i=0;
            $j=0;
            foreach($arrived_codes as $xx)
            {

        ?>

                {
                    id: "line<?php echo $i; ?>",
                    arc: -0.85,
                    alpha: 0.5,
                    title: "<?php echo $arrived_airlines[$j]; ?> <?php echo $arrived_idents[$j]; ?> coming from <?php echo $arrived_locations[$j]; ?> (<?php echo $arrived_names[$j]; ?>), arrived at <?php echo date("H:i", $arrived_times[$j]); ?>.",
                    latitudes: [<?php echo $airport_info['lat']; ?>, <?php echo $arrived_latitudes[$j]; ?>],
                    longitudes: [<?php echo $airport_info['lon']; ?>, <?php echo $arrived_longitudes[$j]; ?>]
                },

            <?php
                $i++;
                $j++;
            }
            ?>

            <?php

            $j=0;
            foreach($enroute_codes as $xx)
            {
            ?>
                {
                    alpha: 0.5,
                    title: "<?php echo $enroute_airlines[$j]; ?> <?php echo $enroute_idents[$j]; ?> from <?php echo $enroute_locations[$j]; ?> (<?php echo $enroute_names[$j]; ?>), expected to arrive at <?php echo date("H:i", $enroute_times[$j]); ?>.",
                    <?php
                    if ($enroute_waypoints_inair[$j][0]!="")
                    {
                        ?>
                        latitudes: [<?php $k = 0; foreach($enroute_waypoints_inair[$j] as $x) { if ($k%2==0) { echo $x.","; } $k++;  } ?>],
                        longitudes: [<?php $k = 0; foreach($enroute_waypoints_inair[$j] as $x) { if ($k%2!=0) { echo $x.","; } $k++; } ?>],
                        arc: 0,
                   <?php
                    }
                    else
                    {
                        ?>
                        arc: -0.85,
                        id: "line<?php echo $i; ?>",

                        <?php
                    }
                ?>


                },


            <?php

                $i++;
                $j++;
            }
            ?>

            <?php

                $j=0;
                foreach($departed_codes as $xx)
            {
            ?>

                    {
                        alpha: 0.5,
                        title: "<?php echo $departed_airlines[$j]; ?> <?php echo $departed_idents[$j]; ?> going to <?php echo $departed_locations[$j]; ?> (<?php echo $departed_names[$j]; ?>). Departed at <?php echo date("H:i", $departed_times[$j]); ?>.",
                                            <?php
                    if ($departed_waypoints_inair[$j][0]!="")
                    {
                        ?>
                        latitudes: [<?php $k = 0; foreach($departed_waypoints_inair[$j] as $x) { if ($k%2==0) { echo $x.","; } $k++;  } ?>],
                        longitudes: [<?php $k = 0; foreach($departed_waypoints_inair[$j] as $x) { if ($k%2!=0) { echo $x.","; } $k++; } ?>],
                        arc: 0,
                   <?php
                    }
                    else
                    {
                        ?>
                        arc: -0.85,
                        id: "line<?php echo $i; ?>",

                        <?php
                    }
                ?>


                },


            <?php

                $i++;
                $j++;
                }
            ?>


        ];



        var images =
        [
            {
                svgPath: starSVG,
                scale: 1,
                title: "<?php echo $airport_info['location']; ?> (<?php echo $airport_info['name']; ?>)",
                latitude: <?php echo $airport_info['lat']; ?>,
                longitude: <?php echo $airport_info['lon']; ?>
            },

        <?php

            $i=0;
            foreach($enroute_codes as $xx)
            {
                if ($enroute_latitudes_inair[$i]!=0)
                {
        ?>

                    {
                        svgPath: planeSVG,
                        title: "<?php echo $enroute_airlines[$i]." ".$enroute_idents[$i]; ?> coming from <?php echo $enroute_locations[$i]; ?> (<?php echo $enroute_names[$i]; ?>), expected to arrive at <?php echo date("H:i", $enroute_times[$i]); ?>",
                        latitude: <?php echo $enroute_latitudes_inair[$i]; ?>,
                        longitude: <?php echo $enroute_longitudes_inair[$i]; ?>,
                        scale: 0.075,
                        rotation: <?php echo $enroute_headings_inair[$i]-90; ?>
                    },

        <?php

                }
                $i++;
            }

        ?>

        <?php

            $i=0;
            foreach($departed_codes as $xx)
            {
                if ($departed_latitudes_inair[$i]!=0)
                {

        ?>
                    {
                        svgPath: planeSVG,
                        title: "<?php echo $departed_airlines[$i]." ".$departed_idents[$i]; ?> going to <?php echo $departed_locations[$i]; ?> (<?php echo $departed_names[$i]; ?>), expected to arrive at <?php echo date("H:i", $departed_arrtimes[$i]); ?>",
                        latitude: <?php echo $departed_latitudes_inair[$i]; ?>,
                        longitude: <?php echo $departed_longitudes_inair[$i]; ?>,
                        scale: 0.075,
                        rotation: <?php echo $departed_headings_inair[$i]-90; ?>
                    },

        <?php

                }
                $i++;
            }
        ?>

        <?php

            $i=0;
            foreach($departed_codes as $xx)
            {

        ?>

                {
                    svgPath: targetSVG,
                    title: "<?php echo $departed_locations[$i]; ?> (<?php echo $departed_names[$i]; ?>)",
                    latitude: <?php echo $departed_latitudes[$i]; ?>,
                    longitude: <?php echo $departed_longitudes[$i]; ?>
                },

        <?php
                $i++;
            }
        ?>

        <?php

            $i=0;
            foreach($departure_codes as $xx)
            {

        ?>

                {
                    svgPath: targetSVG,
                    title: "<?php echo $departure_locations[$i]; ?> (<?php echo $departure_names[$i]; ?>)",
                    latitude: <?php echo $departure_latitudes[$i]; ?>,
                    longitude: <?php echo $departure_longitudes[$i]; ?>
                },

        <?php
                $i++;
            }
        ?>


        <?php

            $i=0;
            foreach($enroute_codes as $xx)
            {

        ?>

                {
                    svgPath: targetSVG,
                    title: "<?php echo $enroute_locations[$i]; ?> (<?php echo $enroute_names[$i]; ?>)",
                    latitude: <?php echo $enroute_latitudes[$i]; ?>,
                    longitude: <?php echo $enroute_longitudes[$i]; ?>
                },

        <?php
                $i++;
            }

        ?>

        <?php

            $i=0;
            foreach($arrived_codes as $xx)
            {

        ?>

                {
                    svgPath: targetSVG,
                    title: "<?php echo $arrived_locations[$i]; ?> (<?php echo $arrived_names[$i]; ?>)",
                    latitude: <?php echo $arrived_latitudes[$i]; ?>,
                    longitude: <?php echo $arrived_longitudes[$i]; ?>
                },

        <?php
                $i++;
            }
        ?>

        <?php
               /*
            $i=0;
            foreach($departure_codes as $xx)
            {

        ?>
                {
                    svgPath: planeSVG,
                    positionOnLine: 0,
                    color: "#585869",
                    animateAlongLine: true,
                    lineId: "line<?php echo $i; ?>",
                    flipDirection: false,
                    loop: true,
                    scale: 0.01,
                    positionScale: 1.8
                },

        <?php
                $i++;
            }
        ?>

        <?php

            //$i=0;
            foreach($arrived_codes as $xx)
            {

        ?>
                {
                    svgPath: planeSVG,
                    positionOnLine: 0,
                    color: "#585869",
                    animateAlongLine: true,
                    lineId: "line<?php echo $i; ?>",
                    flipDirection: true,
                    loop: true,
                    scale: 0.01,
                    positionScale: 1.8
                },

        <?php
                $i++;
            }
        ?>

        <?php

            //$i=0;
            foreach($enroute_codes as $xx)
            {

        ?>
                {
                    svgPath: planeSVG,
                    positionOnLine: 0,
                    color: "#585869",
                    animateAlongLine: true,
                    lineId: "line<?php echo $i; ?>",
                    flipDirection: true,
                    loop: true,
                    scale: 0.01,
                    positionScale: 1.8
                },

        <?php
                $i++;
            }
        ?>

 <?php

            //$i=0;
            foreach($departed_codes as $xx)
            {

        ?>
                {
                    svgPath: planeSVG,
                    positionOnLine: 0,
                    color: "#585869",
                    animateAlongLine: true,
                    lineId: "line<?php echo $i; ?>",
                    flipDirection: false,
                    loop: true,
                    scale: 0.01,
                    positionScale: 1.8
                },

        <?php
                $i++;
            } */
        ?>

        ];

        dataProvider.images = images;
        dataProvider.lines = lines;
        dataProvider.zoomLevel = 10;
        dataProvider.zoomLongitude = <?php echo $airport_info['lon'];  ?>;
        dataProvider.zoomLatitude = <?php echo $airport_info['lat'];  ?>;
        map.dataProvider = dataProvider;
        map.write("mapdiv");
        }
    );
</script>
</head>

<body>
<div id="mapdiv" style="width: 100%; background-color:#000033; height: 500px;"></div>
<div class="col-xs-6">
  <h2>Departures</h2>
  <table class="table table-striped table-condensed">
    <thead>
      <tr>
                  <th>Time<br><font size=1>in <?php echo str_replace("_", "", explode("/", str_replace(":", "", $airport_info['timezone']))[1]); ?></th>
          <th>Flight Number<br><font size=1>Airline</font></th>
        <th>Destination City<br><font size=1>Airport Name</font></th>
      </tr>
    </thead>
    <tbody>
<?php
$i=0;
foreach($departed_codes as $xx)
{
    if ((time()-$departed_times[$i])<(2*60*60)) // Why doesn't this work?
    {
?>

      <tr>
          <td><font color=LightGray><?php echo date("H:i", $departed_times[$i]); ?></font></td>
          <td><font color=LightGray><?php echo $departed_idents[$i]; ?><br><font size=1><?php echo $departed_airlines[$i]; ?></font></font></td>
          <td><font color=LightGray><?php echo $departed_locations[$i]; ?><br><font size=1><?php echo $departed_names[$i]; ?></font></font></td>

          <?php

}$i++;
}

        ?>
        <?php
                            $i=0;
foreach($departure_codes as $xx)
{
    ?>

      <tr>
        <td><?php echo date("H:i", $departure_times[$i]); ?></td>
          <td><?php echo $departure_idents[$i]; ?><br><font size=1><?php echo $departure_airlines[$i]; ?></font></td>
        <td><?php echo $departure_locations[$i]; ?><br><font size=1><?php echo $departure_names[$i]; ?></font></td>

          <?php
    $i++;
}
    ?>
      </tr>
    </tbody>
  </table>
</div>

<!--

    </div>
    <div class="col">
-->



<div class="col-xs-6">
  <h2>Arrivals</h2>
  <table class="table table-striped table-condensed">
    <thead>
      <tr>
        <th>Time<br><font size=1>in <?php echo str_replace("_", "", explode("/", str_replace(":", "", $airport_info['timezone']))[1]); ?></th>
          <th>Flight Number<br><font size=1>Airline</font></th>
        <th>Origin City<br><font size=1>Airport Name</font></th>
      </tr>
    </thead>
    <tbody>
        <?php
$i=0;
foreach($arrived_codes as $xx)
{
    if ((time()-$arrived_times[$i])<(2*60*60)) // Why doesn't this work?
    {
    ?>

      <tr>
          <td><font color=LightGray><?php echo date("H:i", $arrived_times[$i]); ?></font></td>
          <td><font color=LightGray><?php echo $arrived_idents[$i]; ?><br><font size=1><?php echo $arrived_airlines[$i]; ?></font></font></td>
          <td><font color=LightGray><?php echo $arrived_locations[$i]; ?><br><font size=1><?php echo $arrived_names[$i]; ?></font></font></td>

  <?php

        }
    $i++;
    }

        ?>
        <?php
                            $i=0;
foreach($enroute_codes as $xx)
{
    ?>

      <tr <?php /*if (($enroute_times[$i]-$enroute_filedtimes[$i])<3600) { echo "class=\"danger\""; } */ ?>>
                  <td><?php echo date("H:i", $enroute_times[$i]); ?></td>
          <td><font><?php echo $enroute_idents[$i]; ?><br><font size=1><?php echo $enroute_airlines[$i]; ?></font></font></td>

          <td><?php echo $enroute_locations[$i]; ?><br><font size=1><?php echo $enroute_names[$i]; ?></font></td>

          <?php
    $i++;
}
    ?>
      </tr>
    </tbody>
  </table>
</div>






    </body>

</html>
