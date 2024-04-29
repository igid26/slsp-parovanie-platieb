<?php

class exAttach
{
	/**
     *  The imap stream  
     *  @var IMAP stream | false
     */
	private $imap;
	/**
     *  The identifier of the email targeted  
     *  @var int
     */
	private $emailNumber;
	/**
     *  Attachments array  
     *  @var array
     */
	private $attachments;
	/**
     *  Message structure object  
     *  @var object
     */
	private $structure;
	/**
     *  The 'save to' path  
     *  @var string
     */
	private $path;
	/**
     *  An array of zip files and their locations ($path)  
     *  @var array
     */
	private $zips;
	/**
     *  Create the IMAP stream
     *  @param string $hostname the 'mailbox'
     *  @param string $username the user's email address
     *  @param string $password the user's password for the account
     */
	public function __construct($hostname,$username,$password)
	{
		$this->imap = imap_open($hostname,$username,$password) or die('Cannot connect to Gmail: ' . imap_last_error());
	}
	/**
     *  create a search string from parameters
     *  @param array $searchArray an array of keyed parameters
     *  @return string
     */
	private function create_search_string($searchArray)
	{
		$items = array();
		foreach($searchArray as $key=>$value)
		{
			$items[] = strtoupper($key) . ' "' . $value . '"';	
		}
		return implode(" ", $items);
	}
	/**
     *  create files from attachments in a specified directory
     *  @param array $searchArray an array of keyed parameters
	 *  @param string $saveToPath path of where to create files [must end with a /]
     */
	public function get_files($searchArray, $saveToPath=NULL)
	{
		$this->path = $saveToPath;
		
		$searchString = $this->create_search_string($searchArray);
		
		if($emails = imap_search($this->imap, $searchString))
		{
		    $this->emailNumber = end($emails);

		    $overview = imap_fetch_overview($this->imap,$this->emailNumber,0);
	        $this->structure = imap_fetchstructure($this->imap, $this->emailNumber);
		    
			$this->attachments = array();

		    if(isset($this->structure->parts) && count($this->structure->parts)) 
		    {
		        for($i = 0; $i < count($this->structure->parts); $i++) 
		        {
		 			$this->create_new_array($i);
		 
		            if($this->structure->parts[$i]->ifdparameters) 
		            {
		             	$this->check_ifdparams($i);   
		            }
		 
		            if($this->structure->parts[$i]->ifparameters) 
		            {
		                $this->check_ifparams($i);  
		            }
					
		            if($this->attachments[$i]['is_attachment']) 
		            {
						$this->get_file_content($i);
		            }
		        }
		 
		        foreach($this->attachments as $attachment)
		        {
		            if($attachment['is_attachment'] == 1)
		            {
		                $this->make_file($attachment);
		            }
		        }
		    }
		}
		imap_close($this->imap);
	}
	/**
     *  extract any files in a zip archive to a specified location
     *  @param string $unzipDest the path for the extraction [must end with a /]
     */
	public function extract_zip_to($unzipDest=NULL)
	{
		$zip = new ZipArchive;
		foreach($this->zips as $zipfile)
		{
			$res = $zip->open($zipfile);
			if ($res === TRUE)
			{
			  $zip->extractTo($unzipDest);
			  $zip->close();
			}
		}
	}
	/**
     *  creates a file from an attachment and stores path for any zip files
     *  @param array $attachment holds all the info for the attachment
     */
	private function make_file($attachment)
	{
		$filename = $attachment['name'];
		if(empty($filename)) $filename = $attachment['filename'];
		if(empty($filename)) $filename = time() . ".dat";
		$loc = $this->path . $filename;
		if(strtolower(pathinfo($filename, PATHINFO_EXTENSION)) == 'zip') $this->zips[] = $loc;
		$fp = fopen($loc, "w+");
		fwrite($fp, $attachment['attachment']);
		fclose($fp);
	}
	/**
     *  extracts attachment concents and encodes it accordingly
     *  @param int $i the counter for attachments
     */
	private function get_file_content($i)
	{
		$this->attachments[$i]['attachment'] = imap_fetchbody($this->imap, $this->emailNumber, $i+1);
		if($this->structure->parts[$i]->encoding == 3) 
		{ 
			$this->attachments[$i]['attachment'] = base64_decode($this->attachments[$i]['attachment']);
		}elseif($this->structure->parts[$i]->encoding == 4){ 
			$this->attachments[$i]['attachment'] = quoted_printable_decode($this->attachments[$i]['attachment']);
		}
	}
	/**
     *  checks ifdparameters object
     *  @param int $i the counter for attachments
     */
	private function check_ifdparams($i)
	{
		foreach($this->structure->parts[$i]->dparameters as $object) 
		{
			if(strtolower($object->attribute) == 'filename') 
		    {
		    	$this->attachments[$i]['is_attachment'] = true;
		        $this->attachments[$i]['filename'] = $object->value;
		    }
		}
	}
	/**
     *  checks ifparameters object
     *  @param int $i the counter for attachments
     */
	private function check_ifparams($i)
	{
		foreach($this->structure->parts[$i]->parameters as $object) 
		{
			if(strtolower($object->attribute) == 'name') 
		    {
		    	$this->attachments[$i]['is_attachment'] = true;
		        $this->attachments[$i]['name'] = $object->value;
		    }
		}	
	} 
	/**
     *  creates an empty array with default values for an attachment
     *  @param int $i the counter for attachments
     */
	private function create_new_array($i)
	{
		$this->attachments[$i] = array(
			'is_attachment' => false,
		    'filename' => '',
		    'name' => '',
		    'attachment' => ''
		);
	}	
}