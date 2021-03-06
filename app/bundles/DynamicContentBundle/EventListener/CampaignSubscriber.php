<?php
/**
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\DynamicContentBundle\EventListener;

use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\DynamicContentBundle\DynamicContentEvents;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\DynamicContentBundle\Model\DynamicContentModel;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Class CampaignSubscriber.
 */
class CampaignSubscriber extends CommonSubscriber
{
    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * @var DynamicContentModel
     */
    protected $dynamicContentModel;

    /**
     * @var Session
     */
    protected $session;

    /**
     * CampaignSubscriber constructor.
     *
     * @param MauticFactory       $factory
     * @param LeadModel           $leadModel
     * @param DynamicContentModel $dynamicContentModel
     */
    public function __construct(MauticFactory $factory, LeadModel $leadModel, DynamicContentModel $dynamicContentModel, Session $session)
    {
        $this->leadModel = $leadModel;
        $this->dynamicContentModel = $dynamicContentModel;
        $this->session = $session;

        parent::__construct($factory);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD => ['onCampaignBuild', 0],
            DynamicContentEvents::ON_CAMPAIGN_TRIGGER_DECISION => ['onCampaignTriggerDecision', 0],
            DynamicContentEvents::ON_CAMPAIGN_TRIGGER_ACTION => ['onCampaignTriggerAction', 0],
        ];
    }

    public function onCampaignBuild(CampaignBuilderEvent $event)
    {
        $event->addAction(
            'dwc.push_content',
            [
                'label' => 'mautic.dynamicContent.campaign.send_dwc',
                'description' => 'mautic.dynamicContent.campaign.send_dwc.tooltip',
                'eventName' => DynamicContentEvents::ON_CAMPAIGN_TRIGGER_ACTION,
                'formType' => 'dwcsend_list',
                'formTypeOptions' => ['update_select' => 'campaignevent_properties_dynamicContent'],
                'formTheme' => 'MauticDynamicContentBundle:FormTheme\DynamicContentPushList',
                'timelineTemplate' => 'MauticDynamicContentBundle:SubscribedEvents\Timeline:index.html.php',
                'hideTriggerMode' => true,
            ]
        );

        $event->addLeadDecision(
            'dwc.decision',
            [
                'label' => 'mautic.dynamicContent.campaign.decision_dwc',
                'description' => 'mautic.dynamicContent.campaign.decision_dwc.tooltip',
                'eventName' => DynamicContentEvents::ON_CAMPAIGN_TRIGGER_DECISION,
                'formType' => 'dwcdecision_list',
                'formTypeOptions' => ['update_select' => 'campaignevent_properties_dynamicContent'],
                'formTheme' => 'MauticDynamicContentBundle:FormTheme\DynamicContentDecisionList',

            ]
        );
    }

    /**
     * @param CampaignExecutionEvent $event
     */
    public function onCampaignTriggerDecision(CampaignExecutionEvent $event)
    {
        $eventConfig  = $event->getConfig();
        $eventDetails = $event->getEventDetails();
        $lead         = $event->getLead();
        $defaultDwc   = $this->dynamicContentModel->getRepository()->getEntity($eventConfig['dynamicContent']);
        
        if ($defaultDwc instanceof DynamicContent) {
            // Set the default content in case none of the actions return data
            $this->dynamicContentModel->setSlotContentForLead($defaultDwc, $lead, $eventDetails);
        }
        
        $this->session->set('dwc.slot_name.lead.' . $lead->getId(), $eventDetails);

        if ($eventConfig['dwc_slot_name'] === $eventDetails) {
            $event->setResult(true);
            $event->stopPropagation();
        }
    }

    /**
     * @param CampaignExecutionEvent $event
     */
    public function onCampaignTriggerAction(CampaignExecutionEvent $event)
    {
        $eventConfig  = $event->getConfig();
        $lead         = $event->getLead();
        $slot         = $this->session->get('dwc.slot_name.lead.'.$lead->getId());

        $dwc = $this->dynamicContentModel->getRepository()->getEntity($eventConfig['dynamicContent']);
        
        if ($dwc instanceof DynamicContent) {

            if ($slot) {
                $this->dynamicContentModel->setSlotContentForLead($dwc, $lead, $slot);
            }

            $event->setResult($dwc->getContent());

            $event->stopPropagation();
        }
    }
}
