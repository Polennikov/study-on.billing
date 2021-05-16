<?php

namespace App\Controller;

use App\Entity\Transaction;
use App\Repository\CourseRepository;
use JMS\Serializer\SerializerInterface;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/v1/transactions")
 */
class TransactionController extends AbstractController
{
    /**
     * @OA\Get(
     *     path="/api/v1/transactions/",
     *     tags={"Transactions"},
     *     summary="Get all user transactions",
     *     description="Get all user transactions",
     *     operationId="courses.transactions",
     *     @OA\Parameter(
     *          name="type",
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              example="payment|deposit"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="code",
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              example="1112"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="skip_expired",
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              example="1|0"
     *          )
     *     ),
     *     @OA\Response(
     *          response="200",
     *          description="successful operation",
     *          @OA\JsonContent(
     *              type="array",
     *              @OA\Items(
     *                  @OA\Property(
     *                      property="id",
     *                      type="integer",
     *                      example="11"
     *                  ),
     *                  @OA\Property(
     *                      property="created_at",
     *                      type="datetime",
     *                      example="2019-05-20T13:46:07+00:00"
     *                  ),
     *                  @OA\Property(
     *                      property="type",
     *                      type="string",
     *                      example="payment"
     *                  ),
     *                  @OA\Property(
     *                      property="course_code",
     *                      type="string",
     *                      example="landshaftnoe-proektirovanie"
     *                  ),
     *                  @OA\Property(
     *                      property="amount",
     *                      type="number",
     *                      format="float",
     *                      example="159.00"
     *                  ),
     *              )
     *          )
     *     ),
     *     @OA\Response(
     *          response="401",
     *          description="Invalid credentials",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="code",
     *                  type="integer",
     *                  example="401"
     *              ),
     *              @OA\Property(
     *                  property="message",
     *                  type="string",
     *                  example="Invalid credentials."
     *              )
     *          )
     *     )
     * )
     *
     * @Route("/", name="transactions", methods={"GET"})
     *
     * @param Request $request
     * @param SerializerInterface $serializer
     *
     * @return Response
     *
     * @throws \Exception
     */
    public function transactions(Request $request, SerializerInterface $serializer, CourseRepository $courseRepository): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $transactionRepository = $entityManager->getRepository(Transaction::class);

        // Получаем текущего пользователя
        $user = $this->getUser();
        $type = null;
        $code = null;
        $skip_expired = null;

        // Если установлен тип
        if ($request->get('type')) {
            if ('payment' == $request->get('type')) {
                $type = 1;
            } elseif ('deposit' == $request->get('type')) {
                $type = 2;
            }
        }

        // Если установлен код курса
        if ($request->get('code')) {
            // Получаем код курса
            $code = $request->get('code');
        }

        // Если установлен срок окончания аренды
        if ($request->get('skip_expired')) {
            // Получаем срок окончания аренды
            $skip_expired = $request->get('skip_expired');
        }

        $transactions = $transactionRepository->findByTransactionsUsers(
            $type,
            $code,
            $skip_expired,
           $user,
           $courseRepository
        );

        $response = new Response();
        $response->setStatusCode(Response::HTTP_OK);
        $response->setContent($serializer->serialize($transactions, 'json'));
        $response->headers->add(['Content-Type' => 'application/json']);

        return $response;
    }
}
