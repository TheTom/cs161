<?php

// ##########################
// ####### SETTINGS #########
// ##########################

// MAKE SURE YOU SET THIS *ABSOLUTE* PATH TO THE CSV OUTPUT DIRECTORY AND PAY ATTENTION TO THE DATE
// IN THIS CASE, I'M USING THE LOG FROM INFLUENZA 0,1,2 SAUDI ARABIA -- DON'T FORGET TO ADD TRAILING SLASH (/)
$INPUT_PATH = '/Users/bjorkstam/Applications/stem/workspace/BuiltInScenarios/Recorded Simulations/SAU_0_1_2_disease_Influenza-8-2011-03-14/Influenza/human/';
// APPEND WHICHEVER FILE YOU WANT TO READ!
$INPUT_PATH .= 'I_2.csv';


// TEST BY CALLING *** getdata.php?iter=1 *** TO MAKE SURE IT CAN READ THE FILE

// ##########################
// ##### END SETTINGS #######
// ##########################


if (isset($_GET['iter'])) {
    $iter = $_GET['iter'];
    
    // We have yet to find what is requested
    $RESULT_FOUND = false;
    // But if we cant repeatedly find it, go die after $MAX_ITER tries
    $MAX_ITER = 30;
    $CUR_ITER = 0;
    // Lets look for patterns. If the size of the file hasn't changed since last try, then data is most likely not being logged
    $RESULT_SIZE = 0;
    
    if ($iter <= 0) {
        echo json_encode(Array("error" => "INVALID INPUT PARAMETER"));
        $RESULT_FOUND = true;
    }
    while (!$RESULT_FOUND && $CUR_ITER < $MAX_ITER) {
        $handle = fopen($INPUT_PATH, "r");
        if ($handle) {
            // Lets read the whole file. This could be bad if we are dealing with humoungously large files
            if (filesize($INPUT_PATH) > $RESULT_SIZE) {
                $RESULT_SIZE = filesize($INPUT_PATH);
            } else if (filesize($INPUT_PATH) == $RESULT_SIZE) {
                echo json_encode(Array("error" => "FILE NOT UPDATING"));
                $RESULT_FOUND = true; // Not really though
            }
            $contents = fread($handle, filesize($INPUT_PATH));
            
            // Split the document into an array where each row in the file represents a row in the array
            // ie, $rows[11] refers to iteration 11, etc.
            // The first row ($rows[0]) contains the column fields and should be used in construction JSON data
            $rows = preg_split('/\n/', $contents);    

            // Get the requested iteration

            // TODO: Check the CVS output files, STEM seems to add a linebreak after the last line
            // so actual rows = recorded rows - 1
            if ($iter > 0 && $iter < sizeof($rows)-1) {
                // Perhaps the ugliest hack of all time
                // This creates an associative array (title1 => data1, title2 => data2, etc) that we can nicely encode to JSON
                // Title is meant to be whatever value is in the first row of the CSV output
                $title = preg_split('/,/', $rows[0]);
                $data = preg_split('/,/', $rows[$iter]);
                $len = sizeof($title);
                $output = array();
                for ($i=0;$i<$len;$i++) {
                    // We definitely want our NUMERIC data represented as FLOAT (because sometimes its integer, sometimes decimal)
                    $output[$title[$i]] = is_numeric($data[$i]) ? floatval($data[$i]) : $data[$i];
                }
                echo json_encode($output);
                $RESULT_FOUND=true;
            } else {
                // Requested data is not in the file yet, sleep and check again!
                $CUR_ITER++;
                sleep(1);
            }

        } else {
            echo json_encode(Array("error" => "COULD NOT READ DATA FROM FILE"));
            $RESULT_FOUND = true; // Nope, but we don't want the computer to explode
        }
        fclose($handle);
    }
} else if (isset($_GET['params'])) {
    $handle = fopen($INPUT_PATH, "r");
    if ($handle) {
        $contents = fread($handle, filesize($INPUT_PATH));
        $rows = preg_split('/\n/', $contents);    
        $data = preg_split('/,/', $rows[0]);
        $output = Array('params' => $data);
        echo json_encode($output);
    } else {
        echo json_encode(Array("error" => "COULD NOT READ FILE"));
    }
    fclose($handle);
} else {
    echo json_encode(Array("error" => "INVALID INPUT PARAMETER(S)"));
}

?>