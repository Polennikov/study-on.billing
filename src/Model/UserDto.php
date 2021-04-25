<?php

namespace App\Model;

use JMS\Serializer\Annotation as Serialization;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @OA\Schema(
 *     title="UserDto",
 *     description="UserDto"
 * )
 *
 * Class UserDto
 */
class UserDto
{
    /**
     * @OA\Property(
     *     format="email",
     *     title="Email",
     *     description="Email",
     * )
     *
     * @Serialization\Type("string")
     * @Assert\NotBlank()
     * @Assert\Email()
     */
    public $email;

    /**
     * @OA\Property(
     *     format="string",
     *     title="Password",
     *     description="Password",
     * )
     *
     * @Serialization\Type("string")
     * @Assert\NotBlank()
     * @Assert\Length(min=6)
     */
    public $password;
}
