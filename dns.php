<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mail Server Checker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <style>
        #progressContainer {
            width: 100%;
            border: 1px solid #ccc;
            margin-top: 10px;
            position: relative;
        }

        #progressBar {
            height: 20px;
            background-color: #4CAF50;
            width: 0;
            position: absolute;
        }

        #progressText {
            text-align: center;
            position: absolute;
            width: 100%;
            top: 50%;
            transform: translateY(-50%);
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
</head>
<body class="container-fluid">

<div  >
    <div class="row">
        <div class="col-12">
           <center><h1 class="center">MAIL SERVER CHECKER</h1></center> 
        </div>
    </div>
</div>
<div style="margin: 5% 20% auto 39%"  >
    <div class="row mt-3">
        <form id="fileForm" enctype="multipart/form-data">
            <div class="row mt-4">
                <div class="col-6">
                    <label for="fileInput" class="form-label">Insert File To Process</label>
                    <input class="form-control" type="file" name="file" id="fileInput" accept=".txt" required>
                </div>
                <div class="col-6">
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-4">
                    <button class="btn btn-outline-dark" type="button" onclick="startBatchProcess()">Start</button>
                </div>
                <div class="col-5">
                    <button class="btn btn-outline-dark" onclick="downloadResults()"> <i class="fadeIn1"></i> Download</button>
                </div>
            </div>
        </form>
    </div>
    <div class="row mt-4">
        <div class="col-6">
            <div id="progressContainer">
                <div id="progressBar"></div>
                <div id="progressText">0 lines processed</div>
            </div>
        </div>
        <div class="col-6">
            
        </div>
    </div>
</div>

<div id="resultContainer">
    <h3>Mail Server Results</h3>
    <ul id="resultList"></ul>
</div>

<script>
    let batchSize = 500;
let totalEntries = 0;
let processingInProgress = false;
let lastProcessedLine = 0;

function startBatchProcess() {
    if (!processingInProgress) {
        let fileInput = document.getElementById('fileInput');
        let file = fileInput.files[0];

        if (file) {
            let formData = new FormData();
            formData.append('file', file);
            formData.append('batchSize', batchSize);

            let fileReader = new FileReader();
            fileReader.onload = function (e) {
                let content = e.target.result;
                let lines = content.split('\n');
                totalEntries = lines.length;
            };
            fileReader.readAsText(file);

            processingInProgress = true;
            processBatch(formData);
        } else {
            alert('Please choose a file to upload.');
        }
    } else {
        alert('Processing is already in progress. Please wait.');
    }
}

function processBatch(formData) {
    $.ajax({
        url: 'processFile.php',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        success: function (data) {
            let results = JSON.parse(data);

            if (results.length > 0) {
                displayResults(results);
                lastProcessedLine = parseInt(results[results.length - 1].lineNumber);
                updateProgress(lastProcessedLine);
            }

            if (lastProcessedLine < totalEntries) {
                formData.set('lastProcessedLine', lastProcessedLine);
                processBatch(formData);
            } else {
                updateProgress(totalEntries);
                alert('All batches processed!');
                processingInProgress = false;
                lastProcessedLine = 0;
            }
        },
        error: function () {
            alert('Error processing the file.');
            processingInProgress = false;
            lastProcessedLine = 0;
        }
    });
}

function displayResults(results) {
    let resultList = document.getElementById('resultList');

    results.forEach(function (result) {
        let listItem = document.createElement('li');

        let lineNumberElement = document.createElement('div');
        lineNumberElement.textContent = `Line Number: ${result.lineNumber}`;
        listItem.appendChild(lineNumberElement);

        let domainElement = document.createElement('div');
        domainElement.textContent = `Domain: ${result.domain}`;
        listItem.appendChild(domainElement);

        let mailServerElement = document.createElement('div');
        mailServerElement.textContent = `Mail Server: ${result.mailServer}`;
        listItem.appendChild(mailServerElement);

        listItem.style.marginBottom = '15px';

        resultList.appendChild(listItem);
    });
}

function updateProgress(processedLines) {
    let percentage = (processedLines / totalEntries) * 100;
    document.getElementById('progressBar').style.width = percentage + '%';
    document.getElementById('progressText').innerText = processedLines + ' lines processed';

    let nextLineNumber = processedLines + 1;
    let nextLineContent = getNextLineContent(nextLineNumber);

    let processedMessage = `Processed lines: 1-${processedLines}`;
    let nextLineMessage = `Next line: ${nextLineNumber} - ${nextLineContent} (out of ${totalEntries})`;
    alert(processedMessage + '\n' + nextLineMessage);
}

function getNextLineContent(lineNumber) {
    let fileInput = document.getElementById('fileInput');
    let file = fileInput.files[0];

    let fileReader = new FileReader();
    fileReader.readAsText(file);

    return new Promise((resolve, reject) => {
        fileReader.onload = function (e) {
            let content = e.target.result;
            let lines = content.split('\n');
            let nextLineContent = lines[lineNumber - 1].trim();
            resolve(nextLineContent);
        };
        fileReader.onerror = function (e) {
            reject('Error reading file');
        };
    });
}

function addToDownloadedList(fileName) {
    let downloadedList = document.getElementById('downloadedList');
    let listItem = document.createElement('li');
    listItem.textContent = fileName;
    downloadedList.appendChild(listItem);
}

function downloadResults() {
    let results = document.getElementById('resultList').innerText;
    let blob = new Blob([results], { type: 'text/plain' });
    let link = document.createElement('a');
    link.href = window.URL.createObjectURL(blob);
    link.download = `mail_server_results_total_${totalEntries}.txt`;
    link.click();

    // Add the downloaded file to the list
    addToDownloadedList(link.download);
}

</script>

</body>
</html>
