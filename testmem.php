<pre>
<?php 
function tryAlloc($megabyte){
    echo "try allocating {$megabyte} megabyte...";
    $dummy = str_repeat("-",1048576*$megabyte);
    echo "pass.";
    echo "Usage: " . memory_get_usage(true)/1048576; 
    echo " Peak: " . memory_get_peak_usage(true)/1048576;
    echo "\n";
}   
for($i=2;$i<100;$i+=1){
    $limit = $i.'M';
    ini_set('memory_limit', $limit); 
    echo "set memory_limit to {$limit}\n"; 
    echo "memory limit is ". ini_get("memory_limit")."\n";
    tryAlloc($i-1);
}

?>
</pre>
