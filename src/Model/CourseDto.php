<?php

namespace App\Model;

use JMS\Serializer\Annotation as Serialization;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @OA\Schema(
 *     title="CourseDto",
 *     description="Course Dto"
 * )
 * Class CourseDto
 */
class CourseDto
{
    /**
     * @OA\Property(
     *     format="string",
     *     title="type",
     *     description="тип курса",
     *     example="rent"
     * )
     *
     * @Serialization\Type("string")
     * @Assert\NotBlank()
     */
    public $type;

    /**
     * @OA\Property(
     *     format="string",
     *     title="name",
     *     description="наименование курса",
     *     example="Программирование 1С"
     * )
     *
     * @Serialization\Type("string")
     * @Assert\NotBlank()
     */
    public $name;

    /**
     * @OA\Property(
     *     format="string",
     *     title="code",
     *     description="символьный код курса",
     *     example="1115"
     * )
     *
     * @Serialization\Type("string")
     * @Assert\NotBlank()
     */
    public $code;

    /**
     * @OA\Property(
     *     format="float",
     *     title="cost",
     *     description="стоимость курса",
     *     example="350"
     * )
     *
     * @Serialization\Type("float")
     * @Assert\Type("float")
     */
    public $cost;
}
