<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Fms_model extends CI_Model{
    function validate($email, $password){
		$this->db->where('email', $email);
		$this->db->where('password', $password);
		$result = $this->db->get('users');
		return $result->result_array();
	}

    public function checkUser($email,$password,$ekey="users.email",$pkey="users.password"){
		$res = $this->db->select("users.*,
			staff.first_name AS fname,
			staff.last_name AS lname,
			staff.department AS thedepartment,
			partners.name AS thepartner,
			staff.partner_id AS thepartnerid,
			staff.position AS theposition,
			staff.greater_role AS therole")
			->where($ekey,$email)
			->where($pkey,$password)
			->join("staff","staff.staff_id=users.staff_id","inner")
			->join("partners","staff.partner_id=partners.partner_id","left")
			->get("users");
		return $res;
	}

	public function get_all_expenses(){
		$query = $this->db->get('expenses');
		return $query->result_array();
	}
	
	public function save_expense($data){
	    $this->db->insert('expenses',$data);
	}
	
	public function get_all_users(){
		$query = $this->db->select("users.*,
			staff.first_name AS fname,
			staff.last_name AS lname,
			staff.department AS thedepartment,
			partners.name AS thepartner,
			staff.position AS theposition,
			staff.greater_role AS therole")
			->join("staff","staff.staff_id=users.staff_id","inner")
			->join("partners","staff.partner_id=partners.partner_id","left")
			->get("users");
		return $query->result_array();
	}

	// Legacy method - not used in FMS, kept for compatibility
	public function get_staff($val,$select="staff.*"){
		$res = $this->db->select($select)
			->where($val)
			->get("staff")->result_array();
		return $res;
	}

	public function get_all_students(){
		$query = $this->db->get('students');
		return $query->result_array();
	}

	public function count_all_students(){
		$query = $this->db->get('students');
		return $query->num_rows();
	}

	public function count_male_students(){
    	$this->db->where("gender", "male");
		$query = $this->db->get('students');
		return $query->num_rows();
	}

	public function count_female_students(){
		$this->db->where("gender", "female");
		$query = $this->db->get('students');
		return $query->num_rows();
	}

	public function get_students($year,$option){
    	$this->db->where("year",$year);
    	$this->db->where("options",$option);
		$query = $this->db->get('students');
		return $query->result_array();
	}

	public function get_student($regno){
		$this->db->where("regno",$regno);
		$query = $this->db->get('students');
		return $query->result_array();
	}

	public function get_classes(){
    	$this->db->group_by(array("department","options","year"));
		$query = $this->db->get('students');
		return $query->result_array();
	}
}