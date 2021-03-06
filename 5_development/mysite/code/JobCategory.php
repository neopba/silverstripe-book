<?php
class JobCategory extends Page {
	
	static $db = array();
	
	static $allowed_children = array();
	
	static $has_many = array(
		'Jobs' => 'Job',
	);
	
	static $many_many = array(
		'Subscribers' => 'Member' 
	);
	
	static $singular_name = 'Job Category';
	
	static $plural_name = 'Job Categories';
	
	function getCMSFields() {
		$fields = parent::getCMSFields();
		
		$jobsTable = new ComplexTableField(
			$this,
			'Jobs',
			'Job',
			null,
			null,
			"Job.JobCategoryID = {$this->ID}"
		);
		$fields->addFieldToTab('Root.Content.Jobs', $jobsTable);
		
		return $fields;
	}
	
	function ActiveJobs() {
		return $this->Jobs(
			'(ExpiryDate IS NULL OR ExpiryDate > DATE(NOW()))'
		);
	}
	
	function MemberIsSubscribed($member = null){
		if(!$member) $member = Member::CurrentMember();
		$idList = $this->Subscribers()->getIdList();
		return in_array($member->ID, $idList);
	}
	
}

class JobCategory_Controller extends Page_Controller {
	
	function init() {
		parent::init();
		RSSFeed::linkToFeed($this->Link() . 'rss');
	}
	
	function CurrentJob() {
		if(!isset($this->urlParams['ID'])) return false;
		
		return DataObject::get_by_id(
			'Job', 
			(int)$this->urlParams['ID']
		);
	}
	
	function Form() {
		$fields = singleton('Job')->getCMSFields();
		$fields->removeByName('Autor');
		$actions = new FieldSet(
			new FormAction('doSubmitJob', 'Submit')
		);
		$validator = new RequiredFields(
			'Title',
			'Description'
		);
		$form = new Form(
			$this,
			'Form',
			$fields,
			$actions,
			$validator
		);
		return $form;
	}
	
	function doSubmitJob($data, $form) {
		$job = new Job();
		$form->saveInto($job);
		$job->JobCategoryID = $this->dataRecord->ID;
		$job->write();
		$form->sessionMessage(
         'Form successfully submitted',
         'good'
      );
		Director::redirectBack();
		return;
	}
	
	function subscribe() {
		$member = Member::CurrentMember();
		if(!$member) return false;	
		$this->dataRecord->Subscribers()->add($member);
		Director::redirectBack();
		return;
	}

	function unsubscribe() {
		$member = Member::CurrentMember();
		if(!$member) return false;
		$this->dataRecord->Subscribers()->remove($member);
		Director::redirectBack();
		return;
	}
	
	function rss() {
		$feed = new RSSFeed(
			$this->dataRecord->Jobs(),
			$this->Link() . 'rss',
			"Jobs in {$this->dataRecord->Title}",
			"New Job Postings for SilverStripe Developers",
			'Title',
			'Description'
		);
		
		return $feed->outputToBrowser();
	}
	
}
?>