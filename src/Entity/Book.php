<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\BookRepository")
 */
class Book
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $title;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $description;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $isbn;

    /**
     * @ORM\Column(type="date")
     */
    protected $publicationDate;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $picture;

    /**
     * @ManyToOne(targetEntity="Genre")
     * @JoinColumn(name="genre_id", referencedColumnName="id")
     */
    protected $genre;

    /**
     * @ManyToOne(targetEntity="Author")
     * @JoinColumn(name="author_id", referencedColumnName="id")
     */
    protected $author;

    /**
     * @ManyToOne(targetEntity="Language")
     * @JoinColumn(name="language_id", referencedColumnName="id")
     */
    protected $language;
}
