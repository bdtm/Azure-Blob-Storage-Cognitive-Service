<!DOCTYPE html>
<html>
    <head>
        <title>Submission 2 (Akhir)</title>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.0/jquery.min.js"></script>
        <style type="text/css">
	        body{ background-color: #f0ffff; 
				border-top: solid 10px #000;
				color: #333; font-size: .85em;
				margin: 70;
				font-family: "Segoe UI", Verdana, Helvetica, San-Serif;
			}
        </style>
    </head>
    <body>   
    <?php
		require_once 'vendor/autoload.php';
		require_once "./random_string.php";

		use MicrosoftAzure\Storage\Blob\BlobRestProxy;
		use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
		use MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions;
		use MicrosoftAzure\Storage\Blob\Models\CreateContainerOptions;
		use MicrosoftAzure\Storage\Blob\Models\PublicAccessType;

		// $connectionString = "DefaultEndpointsProtocol=https;AccountName=".getenv('ACCOUNT_NAME').";AccountKey=".getenv('ACCOUNT_KEY');
		$connectionString = "DefaultEndpointsProtocol=https;AccountName=bdtmwebapp;AccountKey=6DA+AL3osG3EUlDR1L3A2w3dDOTkQdXnmQk/sJM9Uq2ffRZh1jhxKKQiEwkz7jLm4BwFudvpcnrhQQqhML53Pg==;EndpointSuffix=core.windows.net";

		// Create blob client.
		$blobClient = BlobRestProxy::createBlobService($connectionString);
		$blobResultUrl = "";	
	?>	

    <h1>Submission Akhir - Azure Storage & Cognitive Service</h1> <br><br>
    <script type="text/javascript">
        function processImage() {
            // **********************************************
            // *** Update or verify the following values. ***
            // **********************************************
     
            // Replace <Subscription Key> with your valid subscription key.
     		var subscriptionKey = "43c7574e9aec438da6f289040affefcb";

            // You must use the same Azure region in your REST API method as you used to
            // get your subscription keys. For example, if you got your subscription keys
            // from the West US region, replace "westcentralus" in the URL
            // below with "westus".
            //
            // Free trial subscription keys are generated in the "westus" region.
            // If you use a free trial subscription key, you shouldn't need to change
            // this region.
            var uriBase =
                "https://southeastasia.api.cognitive.microsoft.com/vision/v2.0/analyze";
     
            // Request parameters.
            var params = {
                "visualFeatures": "Categories,Description,Color",
                "details": "",
                "language": "en",
            };
     
            // Display the image.
            var sourceImageUrl = document.getElementById("inputImage").value;
            document.querySelector("#sourceImage").src = sourceImageUrl;
     
            // Make the REST API call.
            $.ajax({
                url: uriBase + "?" + $.param(params),
     
                // Request headers.
                beforeSend: function(xhrObj){
                    xhrObj.setRequestHeader("Content-Type","application/json");
                    xhrObj.setRequestHeader(
                        "Ocp-Apim-Subscription-Key", subscriptionKey);
                },
     
                type: "POST",
     
                // Request body.
                data: '{"url": ' + '"' + sourceImageUrl + '"}',
            })
     
            .done(function(data) {			

                // Show formatted JSON on webpage.
                $("#responseTextArea").val(JSON.stringify(data, null, 2));
				
				// Extract and display the caption and confidence from the first caption in the description object.
				if (data.description && data.description.captions) 
				{
					var caption = data.description.captions[0];					
					$("#output").text(caption.text);
				}

            })     
            .fail(function(jqXHR, textStatus, errorThrown) {
                // Display error message.
                var errorString = (errorThrown === "") ? "Error. " :
                    errorThrown + " (" + jqXHR.status + "): ";
                errorString += (jqXHR.responseText === "") ? "" :
                    jQuery.parseJSON(jqXHR.responseText).message;
                alert(errorString);
            });
        };
   	</script>
   	<fieldset>
   	<legend>
   		<?php
	 	echo "<strong>Select Image To Upload to Azure Blob Storage</strong>"
		?>
   	</legend>	
	<form method="post" action="index.php" enctype="multipart/form-data" >
		<input type="file" name="pic" accept="image/*">
		<input type="submit" name="submit" value="Upload" />
	</form>
	
	 <?php
	 if (isset($_POST['submit'])) 
	 {
		echo "<br />";
		if($_FILES['pic']['name'] == "") 
		{
		  // No file was selected for upload
		   echo "No file was selected for upload";	 
		}
		else			
		{	
			$fileToUpload = $_FILES['pic']['tmp_name'];
			$filename = $_FILES['pic']['name'];

			 $createContainerOptions = new CreateContainerOptions();

				// Set public access policy. Possible values are
				// PublicAccessType::CONTAINER_AND_BLOBS and PublicAccessType::BLOBS_ONLY.
				// CONTAINER_AND_BLOBS:
				// Specifies full public read access for container and blob data.
				// proxys can enumerate blobs within the container via anonymous
				// request, but cannot enumerate containers within the storage account.
				//
				// BLOBS_ONLY:
				// Specifies public read access for blobs. Blob data within this
				// container can be read via anonymous request, but container data is not
				// available. proxys cannot enumerate blobs within the container via
				// anonymous request.
				// If this value is not specified in the request, container data is
				// private to the account owner.
				$createContainerOptions->setPublicAccess(PublicAccessType::CONTAINER_AND_BLOBS);

				// Set container metadata.
				$createContainerOptions->addMetaData("key1", "value1");
				$createContainerOptions->addMetaData("key2", "value2");

				  $containerName = "blockblobs".generateRandomString();

				try {
					// Create container.
					$blobClient->createContainer($containerName, $createContainerOptions);					
					$content = fopen($fileToUpload, "r");		

					//Upload blob
					$blobClient->createBlockBlob($containerName, $filename, $content);

					// List blobs.
					$listBlobsOptions = new ListBlobsOptions();
					
					echo "<strong>File Name & URL at Container Azure Blob Storage: </strong>";
					echo "<br />";
					do{
						$result = $blobClient->listBlobs($containerName, $listBlobsOptions);
						foreach ($result->getBlobs() as $blob)
						{
							echo $blob->getName()." : ".$blob->getUrl()."<br />";
							$blobResultUrl = $blob->getUrl();
						}
						$listBlobsOptions->setContinuationToken($result->getContinuationToken());
					} while($result->getContinuationToken());
					echo "<br />";
				}
				catch(ServiceException $e){
					$code = $e->getCode();
					$error_message = $e->getMessage();
					echo $code.": ".$error_message."<br />";
				}
				catch(InvalidArgumentTypeException $e){
					$code = $e->getCode();
					$error_message = $e->getMessage();
					echo $code.": ".$error_message."<br />";
				}
			}
	 }
	 ?>
   	</fieldset>
	
	<br>
    <strong>Image Analized With Computer Vision</strong>
    <br>
    URL Image Analyze:
	<input type="text" name="inputImage" id="inputImage" size="100"
        value= "<?php echo $blobResultUrl ?>" />			
    <button onclick="processImage()" id="btnclick">Analyze image</button>
    <br><br>
    <div id="wrapper" style="width:1020px; display:table;">
        <div id="jsonOutput" style="width:600px; display:table-cell;">
            Response:
            <br><br>
            <textarea id="responseTextArea" class="UIInput"
                      style="width:580px; height:400px;"></textarea>
        </div>
        <div id="imageDiv" style="width:420px; display:table-cell;">
            Source image:
            <br><br>
            <img id="sourceImage" width="400" />
			<h3 id ="output"></h3>
        </div>
    </div>
    </body>	

	<?php 							
	 if (!empty($blobResultUrl)) 
	 {
		 echo '<script type="text/javascript">processImage();</script>'; 
	 }		 
	?>
</html>
