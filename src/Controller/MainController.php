<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class MainController extends Controller
{
    /**
     * @Route("/", name="main")
     */
    public function index()
    {
        return $this->render('main/index.html.twig', [
            'data' => array(
                'info' => $this->getGeneralInfo(),
                'schedule' => $this->getSchedule(),
                'marks' => $this->getMarks(),
                'token' => $this->get('session')->get('token')
            )
        ]);
    }

    public function getSchedule() {
        $raw = $this->getXML('rozvrh', 'schedule.xsd');
        $raw_header = $raw->getElementsByTagName('hodiny')->item(0)->getElementsByTagName('hod');
        $raw_days = $raw->getElementsByTagName('dny')->item(0)->getElementsByTagName('den');

        $header = array();
        $days = array();
        
        foreach($raw_header as $column) {
            $mapping = array (
                'caption' => 'caption',
                'begintime' => 'start',
                'endtime' => 'end'
            );

            $header[] = $this->getArrayFromElement($column, $mapping);
        }

        foreach($raw_days as $day) {
            $raw_hours = $day->getElementsByTagName('hod');
            $hours = array();

            foreach($raw_hours as $hour) {
                $mapping = array (
                    'zkrpr' => 'shortName',
                    'zkratka' => 'shortName',
                    'zkruc' => 'shortTeacher',
                    'chng' => 'change',
                    'zkrmist' => 'room',
                    'uc' => 'teacher',
                    'pr' => 'name',
                    'nazev' => 'name'
                );

                $hours[] = $this->getArrayFromElement($hour, $mapping);
            }

            $mapping = array(
                'zkratka' => 'name',
                'datum' => 'date'
            );

            $days[] = $this->getArrayFromElement($day, $mapping) + array(
                'hours' => $hours
            );
        }

        $data = array(
            'header' => $header,
            'days' => $days
        );

        return $data;
    }

    public function getMarks() {
        $raw = $this->getXML('znamky', 'marks.xsd');

        $rawSubjects = $raw->getElementsByTagName('predmet');
        $subjects = array();

        foreach ($rawSubjects as $subject) {
            $rawMarks = $subject->getElementsByTagName('znamky')->item(0)->childNodes;
            $marks = array();

            foreach($rawMarks as $mark) {
                if ($mark->nodeType !== 1) {
                    continue;
                }

                $mapping = array(
                    'datum' => 'date',
                    'vaha' => 'weight',
                    'znamka' => 'value',
                    'caption' => 'name'
                );

                $marks[] = $this->getArrayFromElement($mark, $mapping);
            }

            $mapping = array(
                'nazev' => 'name',
                'zkratka' => 'shortName',
            );

            $subjects[] = $this->getArrayFromElement($subject, $mapping) + array(
                'marks' => $marks
            );
        }

        return $subjects;
    }

    public function getGeneralInfo() {
        $raw = $this->getXML('login', 'login.xsd');

        $mapping = [
            'verze' => 'version',
            'jmeno' => 'name',
            'typ' => 'shortType',
            'strtyp' => 'type',
            'skola' => 'school',
            'trida' => 'class',
            'rocnik' => 'grade',
        ];

        return $this->getArrayFromElement($raw, $mapping);
    }

    public function getXML($module, $schema, $arguments = array()) {
        // Get session object
        $session = $this->get('session');

        // Build url
        $url = $session->get('url') . '?' . http_build_query([
            'hx' => $session->get('token'),
            'pm' => $module
        ] + $arguments);
        
        // Download data
        $data = @file_get_contents($url);

        // Check data
        if ($data === false) {
            return false;
        }

        // Load XML
        $xml = new \DOMDocument();
        @$xml->loadXML($data);

        // Check XML
        if ($xml === false) {
            return false;
        }

        // Validate XML structure
        if (!$xml->schemaValidate(SCHEMAS . $schema)) {
            return false;
        }

        return $xml;
    }

    public function getArrayFromElement($node, $mapping) {
        $data = array();

        foreach ($mapping as $key=>$property) {
            $element = $node->getElementsByTagName($key)->item(0);

            if ($element) {
                $data[$property] = $element->nodeValue;
            }
        }

        return $data;
    }
}
