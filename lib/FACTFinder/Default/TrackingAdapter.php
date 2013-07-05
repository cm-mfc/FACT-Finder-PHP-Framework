<?php
/**
 * Tracking adapter using the new tracking API introduced with FF 6.9.
 */
class FACTFinder_Default_TrackingAdapter extends FACTFinder_Abstract_Adapter
{
    // The user clicked on a product / detail view.
    const EVENT_INSPECT = 'inspect';
    // The user checked the availability of a product.
    const EVENT_AVAILABILITY_CHECK = 'availabilityCheck';
    // The user added an item to the cart.
    const EVENT_CART = 'cart';
    // The user bought or booked a product or service.
    const EVENT_BUY = 'buy';
    // The visitor clicked on an item which refines the search query (like filter, or sort).
    const EVENT_REFINE = 'refine';
    // Visitor has given feedback about a ResultNode. Reference Key is optional.
    const EVENT_FEEDBACK = 'feedback';
    // A result (product, banner, ASN element, ...) referenced by the key has been displayed.
    const EVENT_DISPLAY = 'display';
    // A visitor has seen all results for the referenced message.
    const EVENT_DISPLAY_ALL = 'displayAll';
    // A request of the user could be answered from the shop cache.
    const EVENT_CACHE_HIT = 'cacheHit';
    
    /**
     * let the data provider save the tracking params
     *
     * @return boolean $success
     */
    public function applyTracking()
    {
        $this->log->debug("Tracking not available before FF 6.9!");
        return false;
    }

    public function prepareDefaultParams($inputParams) {

        $sid = $inputParams['sid'];
        if (strlen($sid) == 0)
            $sid = session_id();

        $refKey = $inputParams['refKey'];
        if (strlen($refKey) == 0)
            throw new UnexpectedValueException("No RefKey in parameters");

        $params = array('refKey' => $refKey, 'sid' => $sid);

        $optParams = array('userId', 'cookieId', 'price', 'amount', 'positive', 'message');
        foreach ($optParams AS $optParam) {
            if (isset($inputParams[$optParam]) && strlen($inputParams[$optParam]) > 0)
                $params[$optParam] = $inputParams[$optParam];
        }

        return $params;
    }

    public function doTrackingFromRequest($params)
    {
        $params = $this->getParamsParser()->getServerRequestParams();
        $this->prepareDefaultParams($params);
        return $this->applyTracking();
    }

    public function trackEvent($event, $inputParams) {
        $params = $this->prepareDefaultParams($inputParams);

        $events = array(self::EVENT_INSPECT, self::EVENT_AVAILABILITY_CHECK, self::EVENT_CART,
			self::EVENT_BUY, self::EVENT_REFINE, self::EVENT_FEEDBACK, self::EVENT_DISPLAY,
			self::EVENT_DISPLAY_ALL, self::EVENT_CACHE_HIT);

        if (!in_array($event, $events, true)) {
            throw new UnexpectedValueException("Event $event not known.");
        }

        $params['event'] = $event;

        $this->getDataProvider()->setParams($params);

        return $this->applyTracking();
    }
}