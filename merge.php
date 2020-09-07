<?php
function set_metrics($arr, $data, $count){
    $key = strtolower($data[0]);
    if( !isset($arr[$key]) ){
        $arr[$key] = array_fill(1, $count - 1, 0);
        for ($i = 1; $i < 6; $i++) {
            $arr[$key][$i] = $data[$i];
        }
    }
    return $arr;
}
function csvMerge($input_file)
{
    if (!isset($input_file) || (($handle = fopen($input_file, "r")) === FALSE))
        return FALSE;
    $row = 0;
    $count = 0;
    $header = '';
    $merged = array();

    while (($data = fgetcsv($handle)) !== FALSE) {
        $row++;
        if($row == 1){
            $header = $data;
            $count = count($data);
            continue;
        }
        $merged = set_metrics($merged, $data, $count);
        for ($c = 6; $c < $count - 2; $c++) {
            $val = is_numeric($data[$c]) ? $data[$c] : 0;
            $key = strtolower($data[0]);
            if ($val > 0){
                $merged[$key][$count-1] += 1;
            }
            $merged[$key][$c] += $val;
        }
    }
    fclose($handle);
    $merged['header'] = $header;
    return $merged;
}
function generate_csv($merged_data){
    $csv = implode(',', $merged_data['header']);
    foreach ($merged_data as $key => $data) {
        if($key === 'header')
            continue;
        $row = array($key);
        foreach($data as $val){
            $row[] = $val;
        }
        $csv .= "\n" . implode(',', $row);
    }
    return $csv;
}
function download_csv($csv){
    $tmpName = tempnam(sys_get_temp_dir(), 'thought_leaders_download');
    $file = fopen($tmpName, 'w');

    fwrite($file, $csv);
    fclose($file);

    header('Content-Description: File Transfer');
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename=thought_leaders_download.csv');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($tmpName));

    ob_clean();
    flush();
    readfile($tmpName);

    unlink($tmpName);
}
if (isset($_FILES['csvFile']))
    $merged = csvMerge($_FILES['csvFile']['tmp_name']);
if (isset($merged) && $merged !== FALSE){
    $csv = generate_csv($merged);
    download_csv($csv);
}
else {
?>
<form method="post" enctype="multipart/form-data">
    <input type="file" name="csvFile" id="csvFile">
    <input type="submit" value="Upload CSV" name="submit">
</form>
<?php }
