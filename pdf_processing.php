<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>GAGA - PDF Processing Data</title>
    <link rel="icon" type="image/x-icon" href="unitbv.png">
    <?php 
        include_once 'logo.php';
        include_once 'includes/head.php'; 
    ?>
</head>
<body>

<?php 
// Note: If you have input files large than 200kb we highly recommend to check "async" mode example.

// Get submitted form data
$apiKey = 'albertoogrezeanu@yahoo.com_38a8019i77q8ZpPqXV9gMr2Me5qGYG3C2TIXNoGdSE4yEYZD9Q779vXG303N6l4LcRJR7UNOCejqD0M3Q2z51d5kt8Gr9lKi0s2nMbc037X2CJ249vlTJY2TbIM0aQ8FVe852eYeRv5ldGa62Ej3xFoH1f';
$pages = $_POST["pages"];


// 1. RETRIEVE THE PRESIGNED URL TO UPLOAD THE FILE.
// * If you already have the direct PDF file link, go to the step 3.

// Create URL
$url = "https://api.pdf.co/v1/file/upload/get-presigned-url" . 
    "?name=" . urlencode($_FILES["file"]["name"]) .
    "&contenttype=application/octet-stream";
    
// Create request
$curl = curl_init();
curl_setopt($curl, CURLOPT_HTTPHEADER, array("x-api-key: " . $apiKey));
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
// Execute request
$result = curl_exec($curl);

if (curl_errno($curl) == 0)
{
    $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    
    if ($status_code == 200)
    {
        $json = json_decode($result, true);
        
        // Get URL to use for the file upload
        $uploadFileUrl = $json["presignedUrl"];
        // Get URL of uploaded file to use with later API calls
        $uploadedFileUrl = $json["url"];
        
        // 2. UPLOAD THE FILE TO CLOUD.
        
        $localFile = $_FILES["file"]["tmp_name"];
        $fileHandle = fopen($localFile, "r");
        
        curl_setopt($curl, CURLOPT_URL, $uploadFileUrl);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("content-type: application/octet-stream"));
        curl_setopt($curl, CURLOPT_PUT, true);
        curl_setopt($curl, CURLOPT_INFILE, $fileHandle);
        curl_setopt($curl, CURLOPT_INFILESIZE, filesize($localFile));

        // Execute request
        curl_exec($curl);
        
        fclose($fileHandle);
        
        if (curl_errno($curl) == 0)
        {
            $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            
            if ($status_code == 200)
            {
                // 3. CONVERT UPLOADED PDF FILE TO TEXT
                
                ExtractText($apiKey, $uploadedFileUrl, $pages);
            }
            else
            {
                // Display request error
                echo "<p>Status code: " . $status_code . "</p>"; 
                echo "<p>" . $result . "</p>"; 
            }
        }
        else
        {
            // Display CURL error
            echo "Error: " . curl_error($curl);
        }
    }
    else
    {
        // Display service reported error
        echo "<p>Status code: " . $status_code . "</p>"; 
        echo "<p>" . $result . "</p>"; 
    }
    
    curl_close($curl);
}
else
{
    // Display CURL error
    echo "Error: " . curl_error($curl);
}

function ExtractText($apiKey, $uploadedFileUrl, $pages) 
{
    // Create URL
    $url = "https://api.pdf.co/v1/pdf/convert/to/text";
    // Prepare requests params
    $parameters = array();
    $parameters["url"] = $uploadedFileUrl;
    $parameters["pages"] = $pages;
    // Create Json payload
    $data = json_encode($parameters);
    // Create request
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_HTTPHEADER, array("x-api-key: " . $apiKey, "Content-type: application/json"));
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    // Execute request
    $result = curl_exec($curl);
    
    if (curl_errno($curl) == 0)
    {
        $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        if ($status_code == 200)
        {
            $json = json_decode($result, true);

            if (!isset($json["error"]) || $json["error"] == false)
            {
                $resultText = file_get_contents($json["url"]); // Obține textul din fișierul rezultat

                // Returnează textul
                return $resultText;
            }
            else
            {
                // În caz de eroare, returnează un mesaj de eroare
                return "Error: " . $json["message"];
            }
        }
        else
        {
            // În caz de eroare, returnează un mesaj de eroare
            return "Request error - Status code: " . $status_code . "\n" . $result;
        }
    }
    else
    {
        // În caz de eroare, returnează un mesaj de eroare
        return "CURL error: " . curl_error($curl);
    }
    // Cleanup
    curl_close($curl);
}
$text = ExtractText($apiKey, $uploadedFileUrl, $pages);
$text = $text."/final_document";
$patternNumeComplet = '/\b(.*?)(?=\s*Data)/s';
$patternEmail = '/E-mail:\s*([^ ]+)/';
$patternTelefon = '/Număr de telefon:\s*\((\+?\d+)\)\s*(\d+)/';
$patternAdresa = '/Adresă:\s*(.*?)DESPRE/s';
$patternInformatiiPersonale = '/Data nașterii:\s*([\d\/]+)\s*Cetățenie:\s*(.*?)\s*Gen:\s*(.*?)(?=\bNumăr)/s';
$patternExperientaProfesionala = '/EXPERIENȚA PROFESIONALĂ(.*?)EDUCAȚIE/s';
$patternEducatie = '/EDUCAȚIE ȘI FORMARE PROFESIONALĂ(.*?)final_document/s';
//variabile de stocat informatie;
$ultimeleDouaCuvinte = 'n/a';
$email='n/a';
$phoneNumber='n/a';
$final_adress='n/a';
$birthday='n/a';
$cetatenie='n/a';
$gen='n/a';
$profesional='n/a';
$educatie='n/a';
if (preg_match($patternNumeComplet, $text, $matches)) {
    $nume_complet = $matches[1];
    $ultimeleDouaCuvinte = implode(' ', array_slice(str_word_count($nume_complet, 1), -2));
} else {
    echo 'nu s-a gasit un nume!';
}
if(preg_match($patternEmail,$text,$matches)){
    $email = $matches[1];
}else{
    echo 'Nu s-a gasit un email!';
}
if(preg_match($patternTelefon,$text,$matches)){
    $prefix = $matches[1];
    $numar = $matches[2];
    $phoneNumber =  $numar;
    $phoneNumber = str_pad($phoneNumber,10,'0',STR_PAD_LEFT);
}else{
    echo 'Nu s-a gasit niciun numar de telefon';
}
$index=0;
$final_adress='';
if(preg_match($patternAdresa,$text,$matches)){
    foreach($matches as $match){
        $index++;
    }
    for($count=1;$count<$index;$count++){
        if($count==1){
            $final_adress=$final_adress.$matches[$count];
        }else{
            $final_adress=$final_adress.' , '.$matches[$count];
        }
    }
}else{
    echo 'nu s-a gasit nicio adresa';
}
if(preg_match($patternInformatiiPersonale,$text,$matches)){
    $birthday=$matches[1];
    $dateObject=date_create_from_format('d/m/Y',$birthday);
    $birthday=date_format($dateObject,'Y-m-d');
    $cetatenie=$matches[2];
    $cetatenie=ucwords($cetatenie);
    $gen=$matches[3];
    $gen=trim($gen);
    if ($gen == 'Masculin' || $gen == 'masculin') {
        $gen = 'male';
    } elseif ($gen == 'Feminin' || $gen == 'feminin') {
        $gen = 'female';
    } else {
        $gen = 'no mention';
    }
}else{
    echo 'nu e nicio informatie';
}
if (preg_match($patternExperientaProfesionala, $text, $matches)) {
    $profesional = $matches[1];
} else {
    echo 'nu s-a gasit profesie';
}
$index = 0;
if(preg_match($patternEducatie,$text,$matches)){
    $educatie=$matches[1];
    $educatie=substr($educatie,0,-3);
}else{
    echo 'nu s-a gasit educatie';
}
?>
<h6 class="text-center">The data extracted from the pdf were the following:</h6>
<div class="container">
    <?php
    echo '<ul class="list-group list-group-flush">';
    echo '<li class="list-group-item">Full name   : ' . $ultimeleDouaCuvinte . '</li>';
    echo '<li class="list-group-item">Email       :' . $email . '</li>';
    echo '<li class="list-group-item">Phone number:' . $phoneNumber . '</li>';
    echo '<li class="list-group-item">Adress      :' . $final_adress . '</li>';
    echo '<li class="list-group-item">Birthday    :' . $birthday . '</li>';
    echo '<li class="list-group-item">Nationality :' . $cetatenie . '</li>';
    echo '<li class="list-group-item">Gen         :' . $gen . '</li>';
    echo '<li class="list-group-item">Professional:' . $profesional . '</li>';
    echo '<li class="list-group-item">Education   :' . $educatie . '</li>';
    echo '</ul>';
    ?>
</div>
<?php
    $profesional=ucwords(strtolower($profesional));
    $educatie=ucwords(strtolower($educatie));
    $full_name=$ultimeleDouaCuvinte;
    $number_phone=$phoneNumber;
    $adress=$final_adress;
    $date_of_birth=$birthday;
    $nationality=$cetatenie;
    $prof=$profesional;
    $education=$educatie;
    $conn = new mysqli("localhost","root","root","inregistrareconturi");
        if($conn->connect_error){
            die("CONNECTION FAILED");
        }
        $sql_create_table = "CREATE TABLE IF NOT EXISTS membrii_de_recrutat(
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name_user VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone_number VARCHAR(15) NOT NULL,
            adress VARCHAR(255) ,
            date_of_birth varchar(255) NOT NULL,
            nationality VARCHAR(50) NOT NULL,
            gen VARCHAR(10) NOT NULL,
            profesional VARCHAR(255) NOT NULL,
            education VARCHAR(255) NOT NULL
        )";
        if($conn->query($sql_create_table)===TRUE){
            echo '';
        }else{
            echo 'Table can not be create'.$conn->error;
        }
        $sql_check_email = "SELECT * FROM membrii_de_recrutat WHERE email=?";
        $stmt_check_email = $conn->prepare($sql_check_email);
        $stmt_check_email->bind_param("s", $email);
        $stmt_check_email->execute();
        $result_check_email = $stmt_check_email->get_result();

        $sql_check_number_phone = "SELECT * FROM membrii_de_recrutat WHERE phone_number=?";
        $stmt_check_number_phone = $conn->prepare($sql_check_number_phone);
        $stmt_check_number_phone->bind_param("s", $number_phone);
        $stmt_check_number_phone->execute();
        $result_check_number_phone = $stmt_check_number_phone->get_result();

        if ($result_check_email->num_rows > 0) {
            ?>
            <p class="informare text-center mt-3">Email already exists</p>
            <?php
        } elseif ($result_check_number_phone->num_rows > 0) {
            ?>
            <p class="informare text-center mt-3">Number phone already exists</p>
            <?php
        } elseif ($result_check_email->num_rows > 0 && $result_check_number_phone->num_rows > 0) {
            ?>
            <p class="informare text-center mt-3">Email and number phone already exist</p>
            <?php
        } elseif($nationality=='Country'){
            ?>
                <p class="informare text-center mt-3">Choose a country!</p>
            <?php
        }else {
            $sql_insert_data = "INSERT INTO membrii_de_recrutat(full_name_user,email,phone_number,adress,date_of_birth,nationality,gen,profesional,education)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_insert_data = $conn->prepare($sql_insert_data);
            $stmt_insert_data->bind_param("sssssssss",$full_name,$email,$number_phone,$adress,$date_of_birth,$nationality,$gen,$prof,$education);

            if ($stmt_insert_data->execute()) {
                ?>
                <p class="informare-s text-center mt-3">The above data has been successfully entered into the system</p>
                <?php
            } else {
                echo 'ERROR! ' . $stmt_insert_data->error;
                $stmt_insert_data->close();
            }
        }
        $stmt_check_email->close();
        $stmt_check_number_phone->close();
        $conn->close();
    
?>
</body>
</html>

            