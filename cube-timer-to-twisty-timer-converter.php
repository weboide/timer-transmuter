<?php

if(count($argv) != 2) {
  die('needs exactly one input file.'.PHP_EOL);
}

$file = new SplFileObject($argv[1], 'r');
$file->setFlags(SplFileObject::READ_CSV);

$ofile = new SplFileObject($argv[1].'.converted.csv', 'w+');
$ofile->fwrite("Puzzle,Category,Time(millis),Date(millis),Scramble,Penalty,Comment\n");

$lines = 0;
if($file->isReadable()) {
  // skip to line 2
  $file->seek(1);
  while (!$file->eof()) {
    $r = $file->fgetcsv(';');
    if(empty($r) || count($r) == 1 && empty($r[0])) {
      echo 'Skipping empty row.'.PHP_EOL;
      continue;
    }
    $o = [];
    $lines++;

    // input: "Category";"Time (MM:SS.SSS)";"Scrambler";"Date";"Penalty +2 (yes or no)";"DNF (yes or no)";"Section"
    // input: 2x2x2;00:12.180;U2 R F U' F R U2 R' U' R' U2;2017-04-16 12:33;no;no;1492352438760;
    // output: Puzzle,Category,Time(millis),Date(millis),Scramble,Penalty,Comment
    // output: "333";"Normal";"4410";"1481378530544";"D B2 R2 B2 R2 U' F2 D2 B2 R2 D' F L2 U R B' F2 U' F' D' R'";"0";""
    $categories = [
      '2x2x2' => ['222', 'Normal'],
      '3x3x3' => ['333', 'Normal'],
      '4x4x4' => ['444', 'Normal'],
      '5x5x5' => ['555', 'Normal'],
      '3x3x3 One-Handed' => ['333', 'One-Handed'],
      'Pyraminx' => ['pyra', 'Normal'],
      'Skewb' => ['skewb', 'Normal'],
      'Square-1' => ['sq1', 'Normal'],
      'Megaminx' => ['mega', 'Normal'],
    ];
    if(!isset($categories[$r[0]])) {
      die('cant find category: '.$r[0].PHP_EOL);
    }
    $category = $categories[$r[0]];

    $o[] = $category[0];
    $o[] = $category[1];

    // convert time
    $time = [];
    if(!preg_match('/^([0-9]{2}):([0-9]{2}).([0-9]{3})$/', $r[1], $time)) {
      die('cound not process time: '.$r[1]);
    }
    $time_ms = ((int)$time[1])*60000 + (int)$time[2]*1000 + (int)$time[3];
    echo $r[1] .' => '.$time_ms.PHP_EOL;
    $o[] = (string) $time_ms;

    // date
    $timestamp = strtotime($r[3]);
    $o[] = (string)($timestamp*1000);
    echo $r[3] .' => '.($timestamp*1000).PHP_EOL;

    // scramble
    $o[] = $r[2];

    // penalty
    $penalty = "0";
    if($r[5] == 'yes') {
      $penalty = "2";
    }
    elseif($r[4] == 'yes') {
      $penalty = "1";
    }
    $o[] = $penalty;

    // comments
    $o[] = 'converted entry';

    var_dump($o);

    foreach($o as &$i) {
      $i = $i.'_D E L_';
    }
    unset($i);

    $ofile->fputcsv($o, ';');
  }
}
else {
  die('file is not readable'.PHP_EOL);
}

echo "Went through $lines lines".PHP_EOL;
