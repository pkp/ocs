<!DOCTYPE html>

<style>
a {color:blue;font-size:12pt;}
body {background:aquamarine;}
</style>

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>OJS-OCS XML Locale Sync</title>
</head>
<body>
<h2>OJS-OCS XML Locale Data Values Sync</h2>
<form action="localesync.php" method="post" enctype="multipart/form-data">
    <div>
    Select file with locale source text to sync (typically - UA locale from the old version):
    <input type="file" name="sourcefile" id="sourcefile">
    </div>
    <div>
    Select file with new locale layout to sync (typically - US locale from the new version):
    <input type="file" name="destfile" id="destfile">
    </div>
    <input type="submit" value="Sync Process..." name="submit">
</form>
</body>
</html>

<?php

//check is the form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    echo "<hr><p>Processing:<br>";
    //open source file
    $domsource = new DOMDocument('1.0');
    $domsource->load($_FILES['sourcefile']['tmp_name']);
    //open destination
    $domdest = new DOMDocument('1.0');
    $domdest->load($_FILES['destfile']['tmp_name']);

    //locale - get nodelist by tag name
    $sourcenodes = $domsource->getElementsByTagName('locale');
    $destnodes = $domdest->getElementsByTagName('locale');
    $locname = $sourcenodes->item(0)->getAttribute('name');
    $full_name = $sourcenodes->item(0)->getAttribute('full_name');
    echo $locname."<br>";
    $destnodes->item(0)->setAttribute('name', $locname);
    $destnodes->item(0)->setAttribute('full_name', $full_name);
    //messages - get nodelist by tag name
    $sourcenodes = $domsource->getElementsByTagName('message');
    $destnodes = $domdest->getElementsByTagName('message');
    //process nodelist
    foreach ($sourcenodes as $nodecontent) {
        if ($nodecontent->hasAttributes()) {
            $localekeyval = $nodecontent->getAttribute('key');
            $tmptext = (string) $nodecontent->firstChild->nodeValue;
            echo "==========================================================<br>";
            echo "key attribute: `$localekeyval`<br>";
            echo "node value: attribute `$tmptext`<br>";
            $currres = "<strong>-skipped</strong>";
            //process just nodes with existed 'key' attribute
            if (!is_null($localekeyval)) {
                //process all search results in back order
                $i = $destnodes->length - 1;
                while ($i > -1) {
                    $destnodecontent = $destnodes->item($i);
                    if ($destnodecontent->getAttribute('key') == $localekeyval) {
                        $nodecontent = $domdest->importNode($nodecontent, true);
                        //$newelement = $domdest->createTextNode('Some new node!');
                        //$destnodecontent->parentNode->replaceChild($newelement, $destnodecontent);
                        $destnodecontent->parentNode->replaceChild($nodecontent, $destnodecontent);
                        $currres = "-replaced";
                    }

                    $i--;
                }
                //report
                echo $currres."<br>";
            }
        }
    }
    echo "<hr>";
    //save result
    echo "Saved nodes: ".$domdest->save($_FILES['destfile']['name']);
}
?>