<?php

namespace App\Controller;

use App\Entity\Author;
use App\Entity\Book;
use App\Entity\Genre;
use App\Entity\Language;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BookController extends Controller
{

    const BOOKS_PER_PAGE = 10;

    /**
     * @Route("/book/list", name="book_list")
     */
    public function list(Request $request)
    {
        $currentPage = $request->query->getInt('page', 1);
        $query = $request->query->get('query', '');
        $books = $this->getDoctrine()->getRepository(Book::class)->getPage($currentPage, static::BOOKS_PER_PAGE, $query);

        return $this->render('book/list.html.twig', [
            'query' => $query,
            'books' => $books,
            'currentPage' => $currentPage,
            'totalPages' => ceil($books->count() / static::BOOKS_PER_PAGE)
        ]);
    }

    /**
     * @Route("/book/view/{id}", name="book_view", requirements={"id"="\d+"})
     * @param integer $id
     * @return Response
     */
    public function view($id)
    {
        $em = $this->getDoctrine()->getManager();
        $book = $em->getRepository(Book::class)->find($id);

        if (!$book) {
            return $this->render('error/404.html.twig');
        }

        return $this->render('book/view.html.twig', [
            'book' => $book
        ]);
    }

    /**
     * @Route("/book/add", name="book_add")
     */
    public function add(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $r = $request->request;

        $genres = $this->getDoctrine()->getRepository(Genre::class)->getAll();
        $authors = $this->getDoctrine()->getRepository(Author::class)->getAll();
        $languages = $this->getDoctrine()->getRepository(Language::class)->getAll();

        $result = [
            'genres' => $genres,
            'authors' => $authors,
            'languages' => $languages,
            'request' => $r,
            'errors' => []
        ];

        if ($request->getMethod() === 'POST') {
            $result['errors'] = $this->validateBook($request, [
                'genres' => $genres,
                'authors' => $authors,
                'languages' => $languages
            ]);

            if (count($result['errors']) > 0) {
                return $this->render('book/add.html.twig', $result);
            }

            $pictureName = null;

            if ($picture = $request->files->get('picture')) {
                $extension = strtolower(pathinfo($picture->getClientOriginalName(), PATHINFO_EXTENSION));
                $pictureName = uniqid('img_') . '.' . $extension;

                $picture->move($this->get('kernel')->getRootDir() . '/../public/images/book', $pictureName);
            }

            $book = (new Book())
                ->setTitle($r->get('title'))
                ->setDescription($r->get('description'))
                ->setGenre($em->getRepository(Genre::class)->find($r->getInt('genre')))
                ->setAuthor($em->getRepository(Author::class)->find($r->getInt('author')))
                ->setLanguage($em->getRepository(Language::class)->find($r->getInt('language')))
                ->setPublicationDate(new \DateTime($r->get('publication_date')))
                ->setIsbn($r->get('isbn'))
                ->setPicture($pictureName);

            $em->persist($book);
            $em->flush();

            return $this->redirectToRoute('book_list');
        }

        return $this->render('book/add.html.twig', $result);
    }

    /**
     * @Route("/book/edit/{id}", name="book_edit", requirements={"id"="\d+"})
     * @param integer $id
     * @return Response
     */
    public function edit(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $book = $em->getRepository(Book::class)->find($id);
        $r = $request->request;

        if (!$book) {
            return $this->render('error/404.html.twig');
        }

        $genres = $this->getDoctrine()->getRepository(Genre::class)->getAll();
        $authors = $this->getDoctrine()->getRepository(Author::class)->getAll();
        $languages = $this->getDoctrine()->getRepository(Language::class)->getAll();

        $result = [
            'genres' => $genres,
            'authors' => $authors,
            'languages' => $languages,
            'book' => $book,
            'errors' => [],
            'request' => $r
        ];

        if ($request->getMethod() === 'POST') {
            $result['errors'] = $this->validateBook($request, [
                'genres' => $genres,
                'authors' => $authors,
                'languages' => $languages
            ]);

            if (count($result['errors']) > 0) {
                return $this->render('book/edit.html.twig', $result);
            }

            $pictureName = $book->getPicture();

            if ($picture = $request->files->get('picture')) {
                if ($pictureName) {
                    $picturePath = $this->get('kernel')->getRootDir() . '/../public/images/book/' . $book->getPicture();
                    unlink($picturePath);
                }

                $extension = strtolower(pathinfo($picture->getClientOriginalName(), PATHINFO_EXTENSION));
                $pictureName = uniqid('img_') . '.' . $extension;

                $picture->move($this->get('kernel')->getRootDir() . '/../public/images/book', $pictureName);
            }

            $book->setTitle($r->get('title'))
                ->setDescription($r->get('description'))
                ->setGenre($em->getRepository(Genre::class)->find($r->getInt('genre')))
                ->setAuthor($em->getRepository(Author::class)->find($r->getInt('author')))
                ->setLanguage($em->getRepository(Language::class)->find($r->getInt('language')))
                ->setPublicationDate(new \DateTime($r->get('publication_date')))
                ->setIsbn($r->get('isbn'))
                ->setPicture($pictureName);

            $em->flush();

            return $this->redirectToRoute('book_view', ['id' => $book->getId()]);
        }

        return $this->render('book/edit.html.twig', $result);
    }

    /**
     * @Route("/book/delete/{id}", name="book_delete", requirements={"id"="\d+"})
     * @param integer $id
     * @return Response
     */
    public function delete($id)
    {
        $em = $this->getDoctrine()->getManager();
        $book = $em->getRepository(Book::class)->find($id);

        if (!$book) {
            return $this->render('error/404.html.twig');
        }

        if ($book->getPicture()) {
            $picturePath = $this->get('kernel')->getRootDir() . '/../public/images/book/' . $book->getPicture();
            unlink($picturePath);
        }

        $em->remove($book);
        $em->flush();

        return $this->redirectToRoute('book_list');
    }

    private function validateBook(Request $request, $entities)
    {
        $errors = [];
        $r = $request->request;

        if (!$r->has('title') || strlen($r->get('title')) === 0) {
            $errors['title'] = 'You must specify a title';
        }

        if (!$r->has('genre') || !$this->isEntityValid($entities['genres'], $r->getInt('genre'))) {
            $errors['genre'] = 'You must specify a valid genre';
        }

        if (!$r->has('author') || !$this->isEntityValid($entities['authors'], $r->getInt('author'))) {
            $errors['author'] = 'You must specify a valid author';
        }

        if (!$r->has('language') || !$this->isEntityValid($entities['languages'], $r->getInt('language'))) {
            $errors['language'] = 'You must specify a valid language';
        }

        if (!$r->has('publication_date') || !$this->isDateValid($r->get('publication_date'))) {
            $errors['publication_date'] = 'You must specify date in a valid format';
        }

        if (!$r->has('isbn') || !$this->isIsbnValid($r->get('isbn'))) {
            $errors['isbn'] = 'You must enter a valid ISBN';
        }

        if (!$this->isPictureValid($request->files->get('picture'))) {
            $errors['picture'] = 'Invalid book image. Only JPG, JPEG and PNG files are allowed.';
        }

        return $errors;
    }

    private function isEntityValid($entities, $entityId)
    {
        foreach ($entities as $entity) {
            if ((int)$entity->getId() === (int)$entityId) {
                return true;
            }
        }

        return false;
    }

    private function isDateValid($date)
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') == $date;
    }


    private function isIsbnValid($isbn)
    {
        $matchResult = preg_match('/^(?=.{17}$)97(?:8|9)([ -])\d{1,5}\1\d{1,7}\1\d{1,6}\1\d$/', trim($isbn));
        return $matchResult ? true : false;
    }

    private function isPictureValid($picture)
    {
        dump($picture);

        if (!$picture) {
            return true;
        }

        $imageSize = getimagesize($picture->getRealPath());
        if ($imageSize === false) {
            return false;
        }

        dump($picture->getClientOriginalName());

        $allowedExtensions = ['jpg', 'jpeg', 'png'];
        $extension = strtolower(pathinfo($picture->getClientOriginalName(), PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions)) {
            return false;
        }

        return true;
    }
}