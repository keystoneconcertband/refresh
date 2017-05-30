<?
 	include_once("db.class.php");
 	 	
	class memberDB {
		private $db;
		
		/* PUBLIC FUNCTIONS */
		public function __construct() {
			$this->setDB(new Db());
		}
		
		/* SELECT ONLY QUERIES */
		
		// Gets the members by instrument
		public function getMembers($instrument) {
			$this->getDb()->bind("instrument", $instrument);
			return $this->getDb()->query("SELECT firstName, lastName, displayFullName FROM KCB_Members m JOIN KCB_instrument i on m.UID = i.member_uid WHERE i.instrument = :instrument AND disabled = 0 ORDER BY lastName, firstName");
		}
		
		// Gets the member information
		public function getMember($email) {
			$this->getDb()->bind("email", $email);
			return $this->getDb()->row("SELECT m.UID, m.accountType, m.office, m.firstName, m.lastName, m.text, m.carrier, m.displayFullName, m.doNotDisplay, m.lastLogon, m.logonCount, m.auth_cd_guid, m.est_dt_tm, m.lst_tran_dt_tm, m.disabled_dt_tm, m.disabled, m.lst_mod_by, m.est_by FROM KCB_Members m INNER JOIN KCB_email_address e ON e.member_uid=m.uid WHERE e.email_address = :email");
		}

		// Checks that the account shouldn't be locked out because more than 3 auth_code attempts were made in the last hour.
		public function accountLockedStatus($email) {
			$this->getDb()->bind("email", $email);
			return $this->getDb()->single("SELECT l.lst_tran_dt_tm FROM KCB_login_cd l INNER JOIN KCB_Members m ON l.KCB_Members_UID=m.uid INNER JOIN KCB_email_address e ON e.member_uid=m.uid WHERE l.invalid_count >= 3 AND DATE_ADD(l.lst_tran_dt_tm, INTERVAL 1 HOUR) > now() AND e.email_address = :email");
		}

		// Gets only the Auth_cd from the Login Cd table
		public function getAuthCd($email) {
			$this->getDb()->bind("email", $email);
			return $this->getDb()->row("SELECT lc.auth_cd, lc.lst_tran_dt_tm FROM KCB_login_cd lc INNER JOIN KCB_email_address e ON e.member_uid=lc.KCB_Members_UID WHERE e.email_address = :email");
		}
		
		// Get Invalid Count
		public function getInvalidCount($email) {
			$this->getDb()->bind("email", $email);
			return $this->getDb()->single("SELECT lc.invalid_count FROM KCB_login_cd lc INNER JOIN KCB_email_address e ON e.member_uid=lc.KCB_Members_UID WHERE e.email_address = :email");
		}
		
		// Get Auth Cd GUID
		public function getAuthCdGuid($email) {
			$this->getDb()->bind("email", $email);
			$retVal = $this->getDb()->single("SELECT m.auth_cd_guid FROM KCB_Members m INNER JOIN KCB_email_address e ON e.member_uid=m.uid WHERE e.email_address = :email");

			return $retVal;
		}
		
		/* UPDATE FUNCTIONS */
		public function setAuthCd($email, $guid) {
			$this->getDb()->bind("auth_cd_guid", $guid);
			$this->getDb()->bind("email", $email);
			$retVal = $this->getDb()->query("UPDATE KCB_Members m INNER JOIN KCB_email_address e ON e.member_uid=m.uid SET auth_cd_guid = :auth_cd_guid WHERE e.email_address = :email");

			return $retVal;
		}

		// Updates the login_cd invalid count
		public function setLoginCdInvalidCount($email, $ipAddress, $invalidCount) {
			$this->getDb()->bind("email", $email);
			$this->getDb()->bind("invalid_count", $invalidCount);
			$this->getDb()->bind("ip_address", $ipAddress);			
			
			$retVal = $this->getDb()->query("UPDATE KCB_login_cd l INNER JOIN KCB_email_address e ON e.member_uid=l.KCB_Members_UID SET l.invalid_count = :invalid_count, l.ip_address = :ip_address, l.lst_tran_dt_tm = now() WHERE e.email_address = :email");

			return $retVal;
		}
		
		// Upserts login_cd
		public function setLoginCd($uid, $randomNumber, $invalidCount, $ipAddress) {
			$this->getDb()->bind("KCB_Members_UID", $uid);
			$this->getDb()->bind("auth_cd", $randomNumber);
			$this->getDb()->bind("invalid_count", $invalidCount);
			$this->getDb()->bind("ip_address", $ipAddress);
			// Update variables...due to the way PDO handles variable names
			$this->getDb()->bind("invalid_count_upd", $invalidCount);
			$this->getDb()->bind("ip_address_upd", $ipAddress);
			$this->getDb()->bind("auth_cd_upd", $randomNumber);
						
			$retVal = $this->getDb()->query("INSERT INTO KCB_login_cd (KCB_Members_UID, auth_cd, invalid_count, ip_address, lst_tran_dt_tm) VALUES(:KCB_Members_UID, :auth_cd, :invalid_count, :ip_address, now()) ON DUPLICATE KEY UPDATE auth_cd=:auth_cd_upd, ip_address=:ip_address_upd, invalid_count=:invalid_count_upd, lst_tran_dt_tm=now()");	
			
			return $retVal;
		}

		// Update user last login and login count
		public function updateLastLogin($email) {
			$this->getDb()->bind("email", $email);
			$retVal = $this->getDb()->query("UPDATE KCB_Members m INNER JOIN KCB_email_address e ON e.member_uid=m.uid SET logonCount = logonCount + 1, lastLogon = now() WHERE e.email_address = :email");

			return $retVal;
		}
		
		// Logs login information
		public function logLogin($email, $success) {
			$this->getDb()->bind("email", $email);
			$this->getDb()->bind("success", $success);
			$retVal = $this->getDb()->query("INSERT INTO KCB_logon_audit (valid, logonValue, logonDateTime) VALUES(:success, :email, now())");			

			return $retVal;
		}

		/* PRIVATE FUNCTIONS */
		private function getDb() {
			return $this->db;
		}
		
		private function setDb($db) {
        	$this->db = $db;
    	}		
	}
?>