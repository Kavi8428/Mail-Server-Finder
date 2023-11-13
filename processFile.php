<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file']['tmp_name'])) {
    $fileContent = file_get_contents($_FILES['file']['tmp_name']);
    $domains = explode("\n", $fileContent);
    $batchSize = isset($_POST['batchSize']) ? intval($_POST['batchSize']) : 500;

    $start = 0;
    if (isset($_POST['lastProcessedLine']) && is_numeric($_POST['lastProcessedLine'])) {
        $start = $_POST['lastProcessedLine'];
    }

    $end = min($start + $batchSize, count($domains));

    $results = array();

    for ($i = $start; $i < $end; $i++) {
        $domain = trim($domains[$i]);
        if (!empty($domain)) {
            $mailServer = getMailServer($domain);
            $results[] = array('lineNumber' => $i + 1, 'domain' => $domain, 'mailServer' => $mailServer);
        }
    }

    echo json_encode($results);
} else {
    http_response_code(400);
}


function getMailServer($domain) {
    $mailServers = array();

    // Get the MX records for the domain
    getmxrr($domain, $mailServers);

    // Use the first mail server as an example
    if (!empty($mailServers)) {
        return $mailServers[0];
    } else {
        return 'No mail server found';
    }
}
?>
