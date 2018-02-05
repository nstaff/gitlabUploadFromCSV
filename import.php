<?php

require_once('vendor/autoload.php');

use Gitlab\Model\Issue;
use Gitlab\Model\Project;


class Loader
{
    public static function loadComments(Issue $issue)
    {
//        echo "IssueId: $issue->iid\n";
        if (($handle = fopen("comments.csv", "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if ($data[0] == $issue->iid && $data[3] == 'comment') {
                    if($data[5] != "" && !($data[5] == null)){
                        $issue->addComment($data[5]);
                    }
                }
            }
        }
    }


    public static function importIssues(Project $project)
    {
        if (($handle = fopen("issues.csv", "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {

                $milestoneText = $data[5];
                $milestone = self::getMilestoneId($project, $milestoneText);
                $issue = $project->createIssue($data[1], array(
                    'iid' => $data[0],
                    'description'=>$data[2],
                    'assignee'=>$data[12],
                    'milestone' => $milestone,
                ));
                file_put_contents('log.txt', json_encode($issue), FILE_APPEND);
                self::loadComments($issue);

                if ($data[3] == 'closed') {
                    $issue->close();
                }
                echo '.';
            }
        }
    }

    public static function getMilestoneId(Project $project, $milestoneText)
    {
        foreach ($project->milestones() as $milestone) {
            if ($milestone->title == $milestoneText) {
                return $milestone->title;
            }
        }
        return null;
    }

    public static function buildMilestones(Project $project)
    {
        $project->createMilestone('Terminology Development');
        $project->createMilestone('Microbiology');
        $project->createMilestone('NE CARES/Biobank Interactions');
        $project->createMilestone('Metadata');
    }
}

$client = \Gitlab\Client::create('http://10.8.22.222:8090')
    ->authenticate('6tFMsvpXTKy86cyvczAe', \Gitlab\Client::AUTH_URL_TOKEN);
//
//$projectTemp = $client->api('projects')->create('NECARES_Final', array(
//    'description' => 'NECARES Tickets - Trac import2',
//    'issues_enabled' => true
//));

//var_dump($projectTemp);


//$project = new \Gitlab\Model\Project($projectTemp['id'], $client);
$project = new \Gitlab\Model\Project(82, $client);

Loader::buildMilestones($project);

Loader::importIssues($project);


