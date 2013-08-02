<?php
class FACTFinder_Json67_ProductCampaignAdapter extends FACTFinder_Default_ProductCampaignAdapter
{
    /**
     * @return void
     **/
    public function init()
    {
		$this->log->info("Initializing new product campaign adapter.");
        $this->getDataProvider()->setType('ProductCampaign.ff');
        $this->getDataProvider()->setParam('format', 'json');
		$this->getDataProvider()->setParam('do', 'getProductCampaigns');
    }

    /**
     * try to parse data as json
     *
     * @throws Exception of data is no valid JSON
     * @return stdClass
     */
    protected function getData()
    {
        $jsonData = json_decode(parent::getData(), true);
        if ($jsonData === null)
            throw new InvalidArgumentException("json_decode() raised error ".json_last_error());
        return $jsonData;
    }
    
    /**
     * @return array of FACTFinder_Campaign objects
     */
    protected function createCampaigns()
    {
        $campaigns = array();
        $jsonData = $this->getData();
        
        foreach ($jsonData as $campaignData) {
            $campaign = $this->createEmptyCampaignObject($campaignData);
            
            $this->fillCampaignObject($campaign, $campaignData);
            
            $campaigns[] = $campaign;
        }
        
        $campaignIterator = FF::getInstance('campaignIterator', $campaigns);
        return $campaignIterator;
    }
    
    protected function createEmptyCampaignObject($campaignData)
    {
        return FF::getInstance('campaign',
            $this->getEncodingHandler()->encodeServerContentForPage($campaignData['name']),
            '',
            $this->getEncodingHandler()->encodeServerUrlForPageUrl($campaignData['target']['destination'])
        );
    }
    
    protected function fillCampaignObject($campaign, $campaignData)
    {
        switch ($campaignData['flavour'])
        {
        case 'FEEDBACK':
            $this->fillCampaignWithFeedback($campaign, $campaignData);
            $this->fillCampaignWithPushedProducts($campaign, $campaignData);
            break;
        case 'ADVISOR':
            $this->fillCampaignWithAdvisorData($campaign, $campaignData);
            break;
        }
    }
    
    protected function fillCampaignWithFeedback($campaign, $campaignData)
    {
        if (!empty($campaignData['feedbackTexts']))
        {
            $feedback = array();
            
            foreach ($campaignData['feedbackTexts'] as $feedbackData)
            {
                $label = $feedbackData['label'];
                if ($label !== '')
                    $feedback[$label] = $this->getEncodingHandler()->encodeServerContentForPage($feedbackData['text']);
                    
                $id = $feedbackData['id'];
                if ($id !== null)
                    $feedback[$id] = $this->getEncodingHandler()->encodeServerContentForPage($feedbackData['text']);
            }
                    
            $campaign->addFeedback($feedback);
        }
    }
    
    protected function fillCampaignWithPushedProducts($campaign, $campaignData)
    {
        if (!empty($campaignData['pushedProductsRecordList'])) {
            $pushedProducts = array();
            foreach ($campaignData['pushedProductsRecordList'] as $recordData) {
                $record = FF::getInstance('record', $recordData['id']);
                $record->setValues($this->getEncodingHandler()->encodeServerContentForPage($recordData['record']));
                
                $pushedProducts[] = $record;
            }
            $campaign->addPushedProducts($pushedProducts);
        }
    }
    
    protected function fillCampaignWithAdvisorData($campaign, $campaignData)
    {
        $activeQuestions = array();
        
        // The active questions can still be empty if we have already moved down the whole question tree (while the search query still fulfills the campaign condition)
        foreach ($campaignData['activeQuestions'] as $questionData)
        {
            $activeQuestions[] = $this->loadAdvisorQuestion($questionData);
        }

        $campaign->addActiveQuestions($activeQuestions);

        // Fetch advisor tree if it exists
        $advisorTree = array();

        foreach ($campaignData['activeQuestions'] as $questionData)
        {
            $activeQuestions[] = $this->loadAdvisorQuestion($questionData, true);
        }

        $campaign->addToAdvisorTree($advisorTree);
    }
    
    protected function loadAdvisorQuestion($questionData, $recursive = false)
    {
        $answers = array();
        
        foreach ($questionData['answers'] as $answerData)
        {
            $text = $this->getEncodingHandler()->encodeServerContentForPage($answerData['text']);
            $params =  $this->getParamsParser()->createPageLink(
                $this->getParamsParser()->parseParamsFromResultString('') //trim($answerData['params'])) // TODO: Read out that huge params object
            );
            
            $subquestions = array();
            if ($recursive)
                foreach($answerData['questions'] as $subquestionData)
                    $subquestions[] = $this->loadAdvisorQuestion($subquestionData, true);
            
            $answers[] = FF::getInstance('advisorAnswer',
                $text,
                $params,
                $subquestions
            );
        }

        return FF::getInstance('advisorQuestion',
            $this->getEncodingHandler()->encodeServerContentForPage($questionData['text']), 
            $answers
        );
    }
	
}
	