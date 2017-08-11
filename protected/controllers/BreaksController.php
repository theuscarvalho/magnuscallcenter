<?php
/**
 * Acoes do modulo "Pausas".
 *
 * MagnusSolution.com <info@magnussolution.com>
 * 28/10/2012
 */

class BreaksController extends BaseController
{
    public $attributeOrder = 't.id';
    public $filterByUser   = false;

    public function init()
    {
        $this->instanceModel = new Breaks;
        $this->abstractModel = Breaks::model();

        parent::init();
    }

    public function extraFilterCustomOperator($filter)
    {
        //only show no madotory breaks to operator
        $filter .= ' AND mandatory = :clfby';
        $this->paramsFilter[':clfby'] = 0;

        return $filter;
    }
    public static function checkMandatoryBreak($status)
    {
        $mandotory   = false;
        $modelBreaks = Breaks::model()->find(" start_time < '" . date('H:i:s') . "' AND
                        stop_time > '" . date('H:i:s') . "'  AND mandatory = 1");

        if (count($modelBreaks) > 0 && $status != 'INUSE') {

            $tipo = $modelBreaks->name;

            if ($status != 'PAUSED') {

                $modelCampaign = Campaign::model()->find(
                    "id = " . Yii::app()->getSession()->get('id_campaign') . " AND status = 1");

                $turno = Util::detectTurno($modelCampaign);

                AsteriskAccess::instance()->queuePauseMember(Yii::app()->session['username'], $modelCampaign->name);

                $modelOperatorStatus = OperatorStatus::model()->find(
                    "id_user = " . Yii::app()->session['id_user'] . " AND categorizing = 3");
                if (count($modelOperatorStatus) > 0) {
                    $modelOperatorStatus->categorizing = '0';
                    $modelOperatorStatus->save();
                }

                $modelLoginsCampaign              = new LoginsCampaign();
                $modelLoginsCampaign->id_user     = Yii::app()->session['id_user'];
                $modelLoginsCampaign->id_campaign = $modelCampaign->id;
                $modelLoginsCampaign->total_time  = 0;
                $modelLoginsCampaign->starttime   = date('Y-m-d H:i:s');
                $modelLoginsCampaign->turno       = $turno;
                $modelLoginsCampaign->login_type  = 'PAUSE';
                $modelLoginsCampaign->id_breaks   = $modelBreaks->id;
                $modelLoginsCampaign->save();

            }

            $status    = Yii::t('yii', "Pausa obrigatoria:") . ' ' . $tipo;
            $mandotory = true;
        }

        return array($status, $mandotory);
    }
}
