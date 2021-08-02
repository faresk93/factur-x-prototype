<?php


namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class User
 * @package App\Entity
 * @ORM\Entity()
 */
class User extends Person
{
    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Book", mappedBy="person")
     */
    private $books;

    public function addBook(Book $book)
    {
        $this->books[] = $book;

        return $this;
    }

    public function removeBook(Book $book)
    {
        $this->books->removeElement($book);

        return $this;
    }
}
