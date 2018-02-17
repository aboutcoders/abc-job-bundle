<?php
/*
* This file is part of the job-bundle package.
*
* (c) Hannes Schulz <hannes.schulz@aboutcoders.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Abc\Bundle\JobBundle\Controller;

use Abc\Bundle\JobBundle\Api\BadRequestResponse;
use Abc\Bundle\JobBundle\Api\ParameterConstraintViolation;
use Abc\Bundle\JobBundle\Job\Exception\TicketNotFoundException;
use Abc\Bundle\JobBundle\Job\Exception\ValidationFailedException;
use Abc\Bundle\JobBundle\Job\JobInterface;
use Abc\Bundle\JobBundle\Job\Status;
use Abc\Bundle\JobBundle\Model\Job;
use Abc\Bundle\JobBundle\Serializer\DeserializationContext;
use Abc\Bundle\JobBundle\Validator\Constraints as AbcAssert;
use Abc\Bundle\JobBundle\Model\JobList;
use Nelmio\ApiDocBundle\Annotation\Operation;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @author Hannes Schulz <hannes.schulz@aboutcoders.com>
 */
class JobController extends BaseController
{
    /**
     * @Operation(
     *     tags={"AbcJobBundle"},
     *     summary="Returns a collection of jobs",
     *     @SWG\Response(
     *         response="200",
     *         description="Returned when successful",
     *         @SWG\Schema(
     *              type="array",
     *              @Model(type=Abc\Bundle\JobBundle\Model\JobList::class)
     *         )
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Returned when request is invalid",
     *         @Model(type=Abc\Bundle\JobBundle\Api\ErrorResponse::class)
     *     ),
     *     @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         type="integer",
     *         default="1",
     *         requirement="\d+",
     *         required="false",
     *         description="The page number of the result set"
     *     ),
     *     @SWG\Parameter(
     *         name="limit",
     *         in="query",
     *         type="integer",
     *         default="10",
     *         requirement="\d+",
     *         required="false",
     *         description="The page size"
     *     ),
     *     @SWG\Parameter(
     *         name="sortCol",
     *         in="query",
     *         type="string",
     *         default="createdAt",
     *         requirement="(ticket|type|status|createdAt|terminatedAt)",
     *         required="false",
     *         description="The sort column"
     *     ),
     *     @SWG\Parameter(
     *         name="sortDir",
     *         in="query",
     *         type="string",
     *         default="DESC",
     *         requirement="(ASC|DESC)",
     *         required="false",
     *         description="The sort direction"
     *     ),
     *     @SWG\Parameter(
     *         name="criteria",
     *         in="query",
     *         type="map",
     *         required="false",
     *         description="The search criteria defined as associative array, valid keys are ticket|type|status"
     *     )
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function listAction(Request $request)
    {
        $criteria   = $request->query->get('criteria', array());
        $page       = $request->query->get('page', 1);
        $sortColumn = $request->query->get('sortCol', 'createdAt');
        $sortDir    = $request->query->get('sortDir', 'DESC');
        $limit      = $request->query->get('limit', 10);

        if ($errors = $this->validateQueryParameters($page, $sortColumn, $sortDir, $limit, $criteria)) {

            $response = new BadRequestResponse('Invalid query parameters', 'One or more query parameters are invalid');
            $response->setErrors($errors);

            return $this->serialize($response, 400);
        }

        $page   = (int)$page - 1;
        $offset = ($page > 0) ? ($page) * $limit : 0;

        $criteria = $this->filterCriteria($criteria);

        $manager = $this->getJobManager();

        $entities = $manager->findBy($criteria, [$sortColumn => $sortDir], $limit, $offset);
        $count    = $manager->findByCount($criteria);

        $list = new JobList();
        $list->setItems($entities);
        $list->setTotalCount($count);

        return $this->serialize($list);
    }

    /**
     * @ApiDoc(
     * description="Returns a job",
     * section="AbcJobBundle",
     * requirements={
     *   {"name"="ticket", "dataType"="string", "required"=true, "description"="The job ticket"},
     * },
     * output="Abc\Bundle\JobBundle\Model\Job",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     404 = "Returned when node not found",
     *   }
     * )
     *
     * @param string $ticket
     * @return Response
     */
    public function getAction($ticket)
    {
        try {
            return $this->serialize($this->getManager()->get($ticket));
        } catch (TicketNotFoundException $e) {
            return $this->createNotFoundResponse($e->getMessage());
        }
    }

    /**
     * Adds a new job.
     *
     * @ApiDoc(
     *  description="Adds a job",
     *  section="AbcJobBundle",
     *  input="Abc\Bundle\JobBundle\Model\Job",
     *  statusCodes = {
     *     200 = "Returned when successful",
     *     400 = "Form validation error"
     *   }
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function addAction(Request $request)
    {
        $job = $this->deserializeJob($request);

        if ($response = $this->validateJob($job)) {
            return $this->serialize($response, 400);
        }

        try {
            return $this->serialize($this->getManager()->add($job));
        } catch (ValidationFailedException $e) {
            $response = new BadRequestResponse('Invalid request', 'The request contains invalid job parameters');
            $response->setErrors($e->getConstraintViolationList());

            return $this->serialize($response, 400);
        }
    }

    /**
     * Updates a job.
     *
     * @ApiDoc(
     *   description="Updates a job",
     *   section="AbcJobBundle",
     *   input="Abc\Bundle\JobBundle\Model\Job",
     *   output="Abc\Bundle\JobBundle\Model\Job",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     400 = "Returned when validation fails",
     *     404 = "Returned when job not found"
     * })
     *
     * @param Request $request
     * @return Response
     */
    public function updateAction(Request $request)
    {
        $job = $this->deserializeJob($request);

        if ($response = $this->validateJob($job)) {
            return $this->serialize($response, 400);
        }

        try {
            return $this->serialize($this->getManager()->update($job));
        } catch (ValidationFailedException $e) {
            $response = new BadRequestResponse('Invalid request', 'The request contains invalid job parameters');
            $response->setErrors($e->getConstraintViolationList());

            return $this->serialize($response, 400);
        }
    }

    /**
     * Cancels a job.
     *
     * @ApiDoc(
     *   description="Cancels a job",
     *   section="AbcJobBundle",
     *   output="Abc\Bundle\JobBundle\Model\Job",
     *   requirements={
     *     {"name"="ticket", "dataType"="string", "required"=true, "description"="The job ticket"},
     *     {"name"="force", "dataType"="boolean", "required"=false, "default"="false", "description"="The job ticket"},
     *   },
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     404 = "Returned when job not found",
     * })
     *
     * @param string $ticket
     * @param bool   $force Whether to force cancellation (false by default)
     * @return Response
     */
    public function cancelAction($ticket, $force = false)
    {
        try {
            return $this->serialize($this->getManager()->cancel($ticket, $force));
        } catch (TicketNotFoundException $e) {
            return $this->createNotFoundResponse($e->getMessage());
        }
    }

    /**
     * Restarts a job.
     *
     * @ApiDoc(
     *   description="Restarts a job",
     *   section="AbcJobBundle",
     *   output="Abc\Bundle\JobBundle\Model\Job",
     *   requirements={
     *     {"name"="ticket", "dataType"="string", "required"=true, "description"="The job ticket"},
     *   },
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     404 = "Returned when job not found",
     * })
     *
     * @param string $ticket
     * @return Response
     */
    public function restartAction($ticket)
    {
        try {
            return $this->serialize($this->getManager()->restart($ticket));
        } catch (TicketNotFoundException $e) {
            return $this->createNotFoundResponse($e->getMessage());
        }
    }

    /**
     * Returns the logs of a job.
     *
     * @ApiDoc(
     *   description="Returns the logs of a job",
     *   section="AbcJobBundle",
     *   output="array<Abc\Bundle\JobBundle\Model\Log>",
     *   requirements={
     *     {"name"="ticket", "dataType"="string", "required"=true, "description"="The job ticket"},
     *   },
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     404 = "Returned when job not found"
     * })
     *
     * @param string $ticket
     * @return Response
     */
    public function logsAction($ticket)
    {
        try {
            return $this->serialize($this->getManager()->getLogs($ticket));
        } catch (TicketNotFoundException $e) {
            return $this->createNotFoundResponse($e->getMessage());
        }
    }

    /**
     * @param Request $request
     * @param array   $groups
     * @return JobInterface|mixed
     */
    protected function deserializeJob(Request $request, array $groups = [])
    {
        $context = null;
        if (count($groups) > 0) {
            $context = new DeserializationContext();
            $context->setGroups($groups);
        }

        return $this->getSerializer()->deserialize(
            json_encode($request->request->all(), true),
            Job::class,
            'json',
            $context
        );
    }

    /**
     * @param $criteria
     * @return array
     * @throws BadRequestHttpException If invalid status value is set in $criteria
     */
    protected function filterCriteria($criteria)
    {
        if (!is_array($criteria)) {
            throw new HttpException(400, 'Invalid search criteria');
        }

        if (isset($criteria['status'])) {
            try {
                $criteria['status'] = new Status($criteria['status']);
            } catch (\Exception $e) {
                throw new BadRequestHttpException('Invalid status defined in criteria');
            }
        }

        return $criteria;
    }

    /**
     * @param mixed $page
     * @param mixed $sortColumn
     * @param mixed $sortDir
     * @param mixed $limit
     * @param mixed $criteria
     * @return array
     */
    private function validateQueryParameters($page, $sortColumn, $sortDir, $limit, $criteria)
    {
        $validationErrors = [];

        $this->validateQueryParameter($validationErrors, 'page', $page, new Assert\Range(['min' => 1]));
        $this->validateQueryParameter($validationErrors, 'sortCol', $sortColumn, new Assert\Choice(['choices' => ['ticket', 'type', 'status', 'createdAt', 'terminatedAt'], 'message' => 'The value should be a valid sort column']));
        $this->validateQueryParameter($validationErrors, 'sortDir', $sortDir, new Assert\Choice(['choices' => ['ASC', 'DESC'], 'message' => 'The value should be a valid sort direction']));
        $this->validateQueryParameter($validationErrors, 'limit', $limit, new Assert\Range(['min' => 1]));
        $this->validateQueryParameter($validationErrors, 'criteria', $criteria, new Assert\Collection([
            'fields'             => [
                'ticket' => new Assert\Uuid(),
                'status' => new AbcAssert\Status(),
                'type'   => new AbcAssert\JobType(),
            ],
            'allowMissingFields' => true
        ]));

        return $validationErrors;
    }

    /**
     * @param array $validationErrors
     * @param       $name
     * @param       $value
     * @param       $constraint
     */
    private function validateQueryParameter(array &$validationErrors, $name, $value, $constraint)
    {
        $errors = $this->getValidator()->validate($value, $constraint);
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $validationErrors[] = new ParameterConstraintViolation($name, $error->getMessage());
            }
        }
    }

    /**
     * @param $job
     * @return BadRequestResponse|null
     */
    private function validateJob($job)
    {
        if ($this->getParameter('abc.job.rest.validate')) {
            $errors = $this->getValidator()->validate($job);
            if (count($errors) > 0) {
                $response = new BadRequestResponse('Invalid request', 'The request contains invalid job parameters');
                $response->setErrors($errors);

                return $response;
            }
        }

        return null;
    }

    /**
     * @param string $message
     * @return Response
     */
    private function createNotFoundResponse($message)
    {
        return $this->serialize(new BadRequestResponse('Not found', $message), 404);
    }
}