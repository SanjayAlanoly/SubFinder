<html>
  <head>
    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript">
      google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawChart);
	  
      function drawChart() {
		<?php
		$city_row = $city_query->row();
			
		for($d = 15; $d>=0; $d--){ ?>
			var data = google.visualization.arrayToDataTable([
			  ['Day', 'Requests', 'Replies'],
			  ['2004',  <?php${$city_row->name.$name_request.$d}?>,      <?php${$city_row->name.$name_reply.$d}?>],
			  
			]);				
		<?php}?>		
		
        

        var options = {
          title: 'SubFinder Usage Data'
        };

        var chart = new google.visualization.LineChart(document.getElementById('chart_div'));
        chart.draw(data, options);
      }
	  
	   
	  
    </script>
  </head>
  <body>
    <div id="chart_div" style="width: 900px; height: 500px;"></div>
  </body>
</html>