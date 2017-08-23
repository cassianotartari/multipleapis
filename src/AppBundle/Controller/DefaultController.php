<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use GuzzleHttp\Client;
use AppBundle\Entity\SourceApiX;
use AppBundle\Entity\SourceApiXCourse;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.project_dir')).DIRECTORY_SEPARATOR,
        ]);
    }
    
    private function getSourceCourseListContent($url) {
        
        $sourceClient = new Client();
        
        $courseListResponse = $sourceClient->get($url);

        $couseListBodyContent = $courseListResponse
                ->getBody()
                ->getContents();

        return json_decode($couseListBodyContent, true);
    }
    
    private function getSourceCourseContent($id) {
        
        $sourceClient = new Client();
        
        $courseResponse = $sourceClient->get('/courses/{id}', [
            'id' => $id
        ]);

        $courseBodyContent = $courseResponse
                ->getBody()
                ->getContents();

        return json_decode($courseBodyContent, true);
    }

    public function copyApi()
    {
        $sourceApi = new SourceApiX();
        
        $courseList = $this->getSourceCourseListContent('/courses');

        do {
            foreach ($courseList['data'] as $courseId)
            {
                $course = $this->getSourceCourseContent($courseId);
                
                $sourceApiXCourse = new SourceApiXCourse(
                    $course['id'],
                    $course['name'],
                    $course['grade']
                );
                $sourceApi->addCourse($sourceApiXCourse);
            }
            $courseList = $this->getSourceCourseListContent($courseList['next']);
        } while ($courseList['next'] !== '');
        
        $destinationCourses = $sourceApi->getDestinationCourses();
        $this->postToDestinationApi(1, $destinationCourses);
    }
    
    private function postToDestinationApi($schoolId, $destinationCourses) {
        $destinationClient = new Client();
        $destinationClient->post('/school/{schoolId}/courses', [
            'schoolId' => $schoolId,
            'json' => [
                'courses' => $destinationCourses
            ]
        ]);
    }
            
}
