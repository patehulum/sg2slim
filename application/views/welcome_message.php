<?php defined('BASEPATH') OR exit('No direct script access allowed');?>
<div class="container">
	<h1>Welcome to Message Gateway!, This application acting as:</h1>
	<div class="body">
		<ol>
			<li><u>Message Transmission</u> to make comunication between G2 and BC and IATA network.
					<p><?php echo api_url("bcurl") ?></p>
					<p><?php echo api_url("edifly") ?></p>
			</li>
			<li><u>Message Parser</u> for easy message translation using Soap XML and TypeB.</li>
		</ol>
	</div>
	<p class="footer"></p>
	<h1>Message Testing Endpoint:</h1>
	<div class="body">
		<table class="data">
      <tr><th>Operation</th><th>Data</th><th>EndPoint</th></tr>
      <tr> <td>C-IMP reader</td> <td>{"data":"SUBCTXH JOGCTXH\\n.SRGCTXH\\nHELLOWORLD\\nhow are you today"}</td> <td><code>POST <?php echo site_url();?>check/typeb</code> </td> </tr>
      <tr> <td>BC Wrap</td> <td>{"name":"TEST", "content":{ "Username":"TES","Password":"1234"}}</td> <td><code>POST <?php echo site_url();?>check/soapreq</code></td> </tr>

    </table>
	</div>

	<p class="footer"></p>
	<h1>Message Parser</h1>
	<div class="body">
		<p>bellow php array can easily convert as: <u>XML</u> and from XML back to <u>Array/JSON</u></p>
		<?php
		$test=["DOCUMENT"=>[
		  "attr_atribute1"=>"hahaha",
		  "attr_atribute2"=>"whehehe",
		  "MYDATA"=>[
		    "HEADER"=>["JUDUL"=>"mencari yang hilang",'subtitle'=>'kurang kerjaan'],
		    "DETAIL"=>["contributor"=>[['nama'=>'satu','sme'=>'memelas'],['nama'=>'dua','sme'=>'visioner']]]
		  ]
		]];?>
		<table class=coding>
			<tr><th>php Array</th><th>XML</th><th>JSON</th></tr>
			<tr>
				<td><pre>
$test=["DOCUMENT"=>[
	"attr_atribute1"=>"hahaha",
	"attr_atribute2"=>"whehehe",
	"MYDATA"=>[
		"HEADER"=>[
			"JUDUL"=>"mencari yang hilang",
			'subtitle'=>'kurang kerjaan'
		],
	"DETAIL"=>[
		"contributor"=>[
			['nama'=>'satu','sme'=>'memelas'],
			['nama'=>'dua','sme'=>'visioner']
		]
		]
	]
]];</pre> </td>
				<td>
				<?php  $intoxml='<?xml version="1.0" encoding="UTF-8"?> '.arr2xml($test); ?>
		    <pre class="prettyprint lang-xml"><script type="text/javascript">
		      document.write(formatXml('<?php echo $intoxml ?>'));
		    </script></pre></td>
				<td>
					<pre class="prettyprint lang-json"><script type="text/javascript">
			      var myData=<?php  $arr=parsexml($intoxml); echo json_encode($arr) ?>;
			      document.write(JSON.stringify(myData, undefined, 4));
			    </script></pre>
				</td></tr>
		</table>
	</div>
	<p class="footer">Page rendered in <strong>{elapsed_time}</strong> seconds. <?php echo  (ENVIRONMENT === 'development') ?  'CodeIgniter Version <strong>' . CI_VERSION . '</strong>' : '' ?></p>
</div>

</body>
</html>
