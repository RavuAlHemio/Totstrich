<?php

namespace RavuAlHemio\TotstrichBundle\Controller;

use RavuAlHemio\TotstrichBundle\Entity\Deadline;
use RavuAlHemio\TotstrichBundle\Utils\DateTimeUtils;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class DeadlineController extends Controller
{
    public function listAction($intPage = 0, $intPerPage = 0, $blnCompletedAlso = false)
    {
        if ($intPerPage <= 0)
        {
            $intPerPage = (int) $this->container->getParameter('totstrich.deadlines_per_page');
        }

        /** @var \Doctrine\ORM\EntityManager $objEM */
        $objEM = $this->getDoctrine()->getManager();

        $strQuery = '
            SELECT
                d
            FROM
                RavuAlHemioTotstrichBundle:Deadline d
            {{FILTER}}
            ORDER BY
                d.dtmDeadline DESC
        ';

        if ($blnCompletedAlso)
        {
            $strQuery = str_replace('{{FILTER}}', '', $strQuery);
        }
        else
        {
            $strQuery = str_replace('{{FILTER}}', 'WHERE d.blnComplete = FALSE', $strQuery);
        }

        $objQuery = $objEM->createQuery($strQuery);
        $objQuery->setFirstResult($intPage * $intPerPage);
        $objQuery->setMaxResults($intPerPage);

        /** @var Deadline[] $arrDeadlines */
        $arrDeadlines = $objQuery->getResult();

        return $this->render('@RavuAlHemioTotstrich/deadlines.html.twig', [
            'deadlines' => $arrDeadlines
        ]);
    }

    public function addAction(Request $objRequest)
    {
        $strDescription = $objRequest->request->get('description');
        $strDeadlineText = $objRequest->request->get('deadline');

        $dtmDeadline = DateTimeUtils::tryParseFutureDateTime($strDeadlineText);
        if ($dtmDeadline === null)
        {
            return static::makePlainTextResponse('Failed to parse deadline date and time.', 400);
        }

        $objDeadline = new Deadline();
        $objDeadline->strDescription = $strDescription;
        $objDeadline->dtmDeadline = $dtmDeadline;
        $objDeadline->blnComplete = false;

        $objEM = $this->getDoctrine()->getManager();
        $objEM->persist($objDeadline);
        $objEM->flush();

        return $this->redirectToReferrer($objRequest);
    }

    public function showEditorAction($numID)
    {
        /** @var \Doctrine\ORM\EntityManager $objEM */
        $objEM = $this->getDoctrine()->getManager();

        $objDeadline = $this->getDeadlineByID($objEM, $numID);
        if ($objDeadline === null)
        {
            return static::makePlainTextResponse("Deadline with ID $numID not found.", 404);
        }

        $strDateFormat = $this->container->getParameter('totstrich.date_format');
        $strReferrer = $this->get('request')->headers->get('Referer');

        return $this->render('@RavuAlHemioTotstrich/editdeadline.html.twig', [
            'id' => $objDeadline->numID,
            'deadline' => $objDeadline->dtmDeadline->format($strDateFormat),
            'isComplete' => $objDeadline->blnComplete,
            'referrer' => $strReferrer
        ]);
    }

    public function editAction(Request $objRequest, $numID)
    {
        /** @var \Doctrine\ORM\EntityManager $objEM */
        $objEM = $this->getDoctrine()->getManager();

        $objDeadline = $this->getDeadlineByID($objEM, $numID);
        if ($objDeadline === null)
        {
            return static::makePlainTextResponse("Deadline with ID $numID not found.", 404);
        }

        $strDescription = $objRequest->request->get('description');
        $strDeadlineText = $objRequest->request->get('deadline');
        $blnComplete = ($objRequest->request->has('complete') && $objRequest->request->get('complete') !== '0');

        $dtmDeadline = DateTimeUtils::tryParseFutureDateTime($strDeadlineText);
        if ($dtmDeadline === null)
        {
            return static::makePlainTextResponse('Failed to parse deadline date and time.', 400);
        }

        $objDeadline->strDescription = $strDescription;
        $objDeadline->dtmDeadline = $dtmDeadline;
        $objDeadline->blnComplete = $blnComplete;

        $objEM->flush();

        $strOriginalReferrer = $objRequest->request->get('previous-referrer');
        if ($strOriginalReferrer !== null)
        {
            return $this->redirect($strOriginalReferrer, 303);
        }
        else
        {
            // just redirect to the root
            return $this->redirect('/', 303);
        }
    }

    public function completeAction(Request $objRequest, $numID)
    {
        /** @var \Doctrine\ORM\EntityManager $objEM */
        $objEM = $this->getDoctrine()->getManager();

        $objDeadline = $this->getDeadlineByID($objEM, $numID);
        if ($objDeadline === null)
        {
            return static::makePlainTextResponse("Deadline with ID $numID not found.", 404);
        }

        $objDeadline->blnComplete = true;
        $objEM->flush();

        return $this->redirectToReferrer($objRequest);
    }

    protected function redirectToReferrer(Request $objRequest)
    {
        // 303 = temporary redirect, switch to GET
        $strReferrer = $objRequest->headers->get('Referer');
        return $this->redirect($strReferrer, 303);
    }

    protected static function makePlainTextResponse($strText, $intResponseCode = 200)
    {
        return new Response($strText, $intResponseCode, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    /**
     * @param \Doctrine\ORM\EntityManager $objEM
     * @param $numID
     * @return Deadline|null
     */
    protected function getDeadlineByID($objEM, $numID)
    {
        $objQuery = $objEM->createQuery('
            SELECT
                d
            FROM
                RavuAlHemioTotstrichBundle:Deadline d
            WHERE
                d.numID = :id
        ');
        $objQuery->setParameter('id', $numID);

        /** @var Deadline[] $arrDeadlines */
        $arrDeadlines = $objQuery->getResult();
        if (count($arrDeadlines) == 0)
        {
            return null;
        }

        return $arrDeadlines[0];
    }
}
