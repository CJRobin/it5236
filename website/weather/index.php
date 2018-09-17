<!DOCTYPE html>
<html lang="EN">
<head>
  <title>Temperature App</title>
</head>
<body>
  <?php
    if(isset($_POST['submit'])) {
      if(!empty($_POST['city'])) {
        $city = $_POST['city'];
        $url = 'http://api.openweathermap.org/data/2.5/weather?q=' . $city . '&units=imperial&appid=0156a8754337b77f111f3cfaf06367fb';
        $response = file_get_contents($url);

        $data = json_decode($response);
        $temp = $data->{'main'}->{'temp'};
        displayForm($city);
        echo "The temperature in $city is: $temp";
      } else {
        displayForm($city);
        echo "Please enter a city name.<br>";
      }
    } else {
      displayForm('');
    }
    function displayForm($x) {
      echo "<form action='index.php' method='POST'>City Name: <input type='text' name='city' value='$x'><input type='submit' value='Get Weather'></form>";
    }
  ?>
</body>
</html>
