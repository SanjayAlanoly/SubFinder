<?php

class SubFinder extends CI_Controller {
	
	public function __construct()
	{
		parent::__construct();
		$this->load->database();
		$this->load->helper('string');
		date_default_timezone_set('Asia/Calcutta');
	}
	
		
	
	public function subreq(){
	
		$phonevol = preg_replace('/^91/', '', $_REQUEST['msisdn']); // Gupshup uses a 91 at the start. Remove that.
		$keyword = strtolower($_REQUEST['keyword']);
		$content = $_REQUEST['content'];
		
		//Get the details of the volunteer who has requested the substitution
		$query = $this->db->from('volunteer')->where('phone', $phonevol)->get(); //Get details of volunteer who send the request message
		
		if($query->num_rows() == 0){
			echo "Message to $phonevol: Your phone number doesn't exist on the database. Please contact your Ops fellow for more details<br>";
			exit();
		}
		
		$req_vol = $query->row();
		
		//Generate the random 4 character request id
		$req_id = substr(str_shuffle(str_repeat('abcdefghijklmnopqrstuvwxyz0123456789',5)),0,4);
		
		
		//Check whether the request id already exist
		$query = $this->db->from('request')->where('req_id',$req_id)->get();
		
		while(1){
			if($query->num_rows() > 0){
				$req_id = substr(str_shuffle(str_repeat('abcdefghijklmnopqrstuvwxyz0123456789',5)),0,4);
				$query = $this->db->from('request')->where('req_id',$req_id)->get();
			}
			else{
				break;
			}
		}
		
		//Remove the keyword from the content
		$content = str_replace('SREQ ','', $content);
		$content = trim($content);
		
		
		//Check if request message contains argument for specifying sub requirement for a class which is not the next class
		if(is_numeric($content))	
			$extra = $content;
		else
			$extra = 0;
		
		//Create the date and time in format suitable for the MySQL table
		$day_time = new DateTime("Next $req_vol->day_time +$extra week");
		$date_time = $day_time->format('Y-m-d H:i:s');
		
		//Check if the volunteer has already made a request for the same day
		$query = $this->db->from('request')->where('req_vol_id',$req_vol->id)->where('date_time',$date_time)->get();
		
		if($query->num_rows() > 0){
			echo "Message to $phonevol: You have already made a substitution request for the same day<br>";
			exit();
		}
		
		//Message the volunteer about the the ID number
		echo "Request Vol: $req_vol->name <br>";
		echo "Message to $phonevol: Your sub request has been registered with the ID number $req_id <br>";
		

				

		//Insert the request details into the 'request' table
		$data = array(
		   'req_id' => $req_id ,
		   'req_vol_id' => $req_vol->id ,
		   'date_time' => $date_time
		);

		$this->db->insert('request', $data); 
		
		$this->db->from('volunteer')->where_not_in('id', $req_vol->id)->where('city',$req_vol->city);
		$query = $this->db->get();
		
		
		foreach ($query->result() as $selectedvol){
			
			echo "<br>Selected Vol: $selectedvol->name <br>";
			echo "Message to $selectedvol->phone: 
			$req_vol->name requires a substitute at $selectedvol->center 
			on $selectedvol->day_time (REQ ID: $req_id ) <br>" ;
				
			
		}
	}
	
	public function test(){
		//$d = "thur";
		//$t = new DateTime("Next $d 4:30 PM +1 week");
		//echo $t->format('r') . PHP_EOL;
		//echo $t->format('Y-m-d H:i:s');
		
		echo substr(str_shuffle(str_repeat('abcdefghijklmnopqrstuvwxyz0123456789',5)),0,4);

	}
	
	
	
	public function subfor(){
		
		$phonevol = preg_replace('/^91/', '', $_REQUEST['msisdn']); // Gupshup uses a 91 at the start. Remove that.
		$keyword = strtolower($_REQUEST['keyword']);
		$content = $_REQUEST['content'];
		
		
		//Get the details of the volunteer who has send the message
		$query = $this->db->from('volunteer')->where('phone', $phonevol)->get();
		
		if($query->num_rows() == 0){
			echo "Message to $phonevol: Your phone number doesn't exist on the database. Please contact your Ops fellow for more details<br>";
			exit();
		}
				
		$int_vol = $query->row();
		
		$flag_req_exist = false;
		
		$content = str_replace('SFOR ','', $content);
		$content = trim($content);
		
		//Check if the request id that is specified in the message exist
		$query = $this->db->from('request')->get();
		foreach($query->result() as $request){
			if($content == $request->req_id)
				$flag_req_exist = true;
		}
		
		//Check if the volunteer interest has already been registered in the database
		$flag_int_already_reg = false;
		
		if($flag_req_exist == false)
			echo "Message to $int_vol->phone: The request ID that you have specified doesn't exist. Please check and resend message<br>";
		else{
			$query0 = $this->db->from('request')->where('req_id',$content)->get();
			$request = $query0->row();
			$name = "int_vol_";
			for($i = 1; $i<=20; $i++){
				if($request->{$name.$i} == $int_vol->id){
					$flag_int_already_reg = true;
					echo "Message to $int_vol->phone: Your substitution interest has already been registered<br>";
					break;
				}
			}
		}
		
		
		//Check if the sub request is still open
		if($flag_req_exist == true && $flag_int_already_reg == false){			
			$query1 = $this->db->from('request')->where('req_id',$content)->get();
			$request = $query1->row();
			if($request->sub_vol != -1)
				echo "Message to $int_vol->phone: We have already found a volunteer for the substitution request<br>";
			else{
				$name = "int_vol_";
				for($i = 1; $i<=20; $i++){
					if($request->{$name.$i} == -1){
						
						$data = array(
						   $name.$i => $int_vol->id ,
						);
						
						//Insert the interested volunteers id into the 'request' table
						$this->db->where('req_id',$content)->update('request', $data); 
						
						echo "Message to $int_vol->phone: Your interest to substitute has been registered<br>";
						$query2 = $this->db->from('volunteer')->where('id',$request->req_vol_id)->get();
						
						//Inform the volunteer who has made the request about the interested volunteer
						$req_vol = $query2->row();
						echo "Message to $req_vol->phone: $int_vol->name wants to sub for you(REQ ID: $request->req_id)(Vol No: $i)<br>";
						break;
					}
				}
			}		
		}	
	}
	
	public function subconf(){
	
		$phonevol = preg_replace('/^91/', '', $_REQUEST['msisdn']); // Gupshup uses a 91 at the start. Remove that.
		$keyword = strtolower($_REQUEST['keyword']);
		$content = $_REQUEST['content'];
		
		//Get the details of the volunteer who has made the request
		$query = $this->db->from('volunteer')->where('phone', $phonevol)->get();
			
		if($query->num_rows() == 0){
			echo "Message to $phonevol: Your phone number doesn't exist on the database. Please contact your Ops fellow for more details<br>";
			exit();
		}
				
		$req_vol = $query->row();
		
		list($req_id, $vol_no) = explode(" ",str_replace('SCNF ','', $content));
		$req_id = trim($req_id);
		$vol_no = trim($vol_no);
		
		//Check if the request id that has been specified exist
		$flag_req_exist = false;
		$query = $this->db->from('request')->where('req_id',$req_id)->get();
			
		if($query->num_rows() > 0)
			$flag_req_exist = true;
		else{
			echo "Message to $req_vol->phone: Please check the request ID that you have specified<br>";
			exit();
		}
		
		
		$flag_vol_exist = false;
		
		$request = $query->row();
		$name = "int_vol_";
		
		//Check if the volunteer number specified exist
		$query1 = $this->db->from('volunteer')->where('id',$request->{$name.$vol_no})->get();
		if($query1->num_rows() > 0)
			$flag_vol_exist = true;
		else{
			echo "Message to $req_vol->phone: Please the volunteer number you have specified<br>";
			exit();
		}
		
		//If both are true then update the confirmed sub(sub_conf) field in the request table
		if($flag_req_exist==true && $flag_vol_exist==true){	
			$sub_vol = $query1->row();
			
			$data = array(
						   'sub_vol' => $sub_vol->id
						);
			$this->db->where('req_id',$req_id)->update('request', $data); 
			
			echo "Message to $sub_vol->phone: You have been selected to sub for $req_vol->name at $req_vol->center on $req_vol->day_time<br>";
			echo "Message to $req_vol->phone: You have confirmed $sub_vol->name to sub for on $req_vol->day_time<br>";
		}	
	

	}
	
	
	public function subrem(){
		
		$phonevol = preg_replace('/^91/', '', $_REQUEST['msisdn']); // Gupshup uses a 91 at the start. Remove that.
		$keyword = strtolower($_REQUEST['keyword']);
		$content = $_REQUEST['content'];
		
		
		//Get details about the volunteer who has send the message
		$query = $this->db->from('volunteer')->where('phone', $phonevol)->get();
			
		if($query->num_rows() == 0){
			echo "Message to $phonevol: Your phone number doesn't exist on the database. Please contact your Ops fellow for more details<br>";
			exit();
		}
				
		$req_vol = $query->row();	
		
		$content = str_replace('SREM ','', $content);
		$content = trim($content);
		
		//Check if the request id that has been specified exist
		
		$query = $this->db->from('request')->where('req_id',$content)->get();
			
		if($query->num_rows() == 0){
			echo "Message to $req_vol->phone: Please check the request ID that you have specified<br>";
			exit();
		}
		
		//Check if the volunteer making the remove request is the same as the one who made the sub request
		
		$request = $query->row();
		
		if($request->req_vol_id != $req_vol->id){
			echo "Message to $req_vol->phone: You cannot remove a request created by another volunteer<br>";
			exit();
		}
		
		//Delete and inform the volunteer about the same
		$this->db->delete('request', array('req_id' => $content)); 
		
		echo "Message to $req_vol->phone: Your request '$content' has been removed<br>";
		
		
		//Inform all the volunteers who had expressed interest in subbing about the removal of the request
		$name = "int_vol_";
		for($i = 1; $i<=20; $i++){
			if($request->{$name.$i} != -1){
				
				$query1 = $this->db->from('volunteer')->where('id',$request->{$name.$i})->get();
				$int_vol = $query1->row();	
				echo "Message to $int_vol->phone: The substitution request '$content' is no longer required<br>";
				
			}
		}
		
	}
}
//http://localhost/index.php/subfinder/subreq?msisdn=919633977657&keyword=SREQ&content=SREQ
//http://localhost/index.php/subfinder/subfor?msisdn=919746419487&keyword=SFOR&content=SFOR+9tdn
//http://localhost/index.php/subfinder/subconf?msisdn=919746419487&keyword=SCNF&content=SCNF+9tdn+2
//http://localhost/index.php/subfinder/subrem?msisdn=919746419487&keyword=SREM&content=SREM+9tdn
?>		

