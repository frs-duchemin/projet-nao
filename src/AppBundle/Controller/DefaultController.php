<?php

namespace AppBundle\Controller;


use AppBundle\Entity\Contact;
use AppBundle\Entity\EmailNewsletter;
use AppBundle\Entity\Taxrefv10;
use AppBundle\Entity\Observation;
use AppBundle\Form\EmailNewsletterType;
use AppBundle\Form\ObservationFrontType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use AppBundle\Form\ContactType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $listLastObservations = $em->getRepository('AppBundle:Observation')->findLastObservations(3);

        $emailNewsletter = new EmailNewsletter();
        // On crée le formulaire
        $formNewsletter = $this->createForm(EmailNewsletterType::class, $emailNewsletter);


        return $this->render('AppBundle:Front:index.html.twig', array(
            'listLastObservations' => $listLastObservations,
            'formNews' => $formNewsletter->createView()
        ));
    }

    /**
     *
     * @Route("/viewallspecies/{page}", name="view_all_species")
     * @param $page
     * @return \Symfony\Component\HttpFoundation\Response
     * @Method({"GET"})
     *
     */
    public function viewAllSpeciesAction($page)
    {
        $em = $this->getDoctrine()->getManager();
        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $em->getRepository('AppBundle:Taxrefv10')->getAll(), /* query NOT result */
            $page/*page number*/,
            25/*limit per page*/
        );
        return $this->render('AppBundle:Front:viewAllSpecies.html.twig', array(
            'pagination' => $pagination,
        ));
    }

    /**
     *
     * @Route("/viewallobservations/{page}", name="view_all_observations")
     * @param $page
     * @return \Symfony\Component\HttpFoundation\Response
     * @Method({"GET"})
     *
     */
    public function viewAllObservationsAction($page)
    {
        $em = $this->getDoctrine()->getManager();
        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $em->getRepository('AppBundle:Observation')->findObsWithStatus($this->getParameter('var_project')['status_obs_valid']), /* query NOT result */
            $page/*page number*/,
            25/*limit per page*/
        );
        return $this->render('AppBundle:Front:viewAllObservations.html.twig', array(
            'pagination' => $pagination,
        ));
    }

    /**
     *
     * @Route("/viewonespecies/{id}", name="view_one_species")
     * @param $taxrefv10
     * @return \Symfony\Component\HttpFoundation\Response
     * @Method({"GET"})
     *
     */
    public function viewOneSpeciesAction(Taxrefv10 $taxrefv10)
    {
        $em = $this->getDoctrine()->getManager();
        $listObservations = $em->getRepository('AppBundle:Observation')->findBy(array('espece' => $taxrefv10));
        return $this->render('AppBundle:Front:viewOneSpecies.html.twig', array(
            'taxrefv10' => $taxrefv10,
            'listObservations' => $listObservations,
        ));
    }

    /**
     *
     * @Route("/viewoneobservation/{id}", name="view_one_observation")
     * @param $observation
     * @return \Symfony\Component\HttpFoundation\Response
     * @Method({"GET"})
     *
     */
    public function viewOneObservationAction(Observation $observation)
    {
        return $this->render('AppBundle:Front:viewOneObservation.html.twig', array(
            'observation' => $observation,
        ));
    }

    /**
     *
     * @Route("/createobservation/{id}", name="create_observation")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @Security("has_role('ROLE_USER')")
     * @Method({"GET", "POST"})
     *
     */
    public function createObservationAction(Request $request, $id = null)
    {
        $em = $this->getDoctrine()->getManager();
        $observation = new Observation();
        if ($id !== null)
        {
            $species = $em->getRepository('AppBundle:Taxrefv10')->findOneById($id);
            $observation->setEspece($species);
        }
        $observation->setStatus('waiting');
        $observation->setAuthor($this->getUser());
        $form   = $this->get('form.factory')->create(ObservationFrontType::class, $observation);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($observation);
            $em->flush();
            $request->getSession()->getFlashBag()->add('success', 'Observation bien enregistrée.');
            return $this->redirectToRoute('view_one_observation', array('id' => $observation->getId()));
        }
        return $this->render('AppBundle:Front:createObservation.html.twig', array(
            'form' => $form->createView(),
            'observation' => $observation,
        ));
    }

    /**
     *
     * @Route("/learnmore", name="learn_more")
     * @return \Symfony\Component\HttpFoundation\Response
     * @Method({"GET"})
     *
     */
    public function learnMoreAction()
    {
        return $this->render('AppBundle:Front:learnMore.html.twig');
    }

    /**
     *
     * @Route("/landing", name="landing")
     * @return \Symfony\Component\HttpFoundation\Response
     * @Method({"GET"})
     *
     */
    public function landingAction()
    {
        return $this->render('AppBundle:Landing:index.html.twig');
    }

    /**
     * @Route("/viewmyobservation/{page}", name="view_my_observation")
     * @param $page
     * @return Response
     */
    public function viewMyObservationAction($page)
    {
        // On récupère l'utilisateur courant
        $user = $this->getUser();
        if ($user !== null) {

            $em = $this->getDoctrine()->getManager();
            $paginator  = $this->get('knp_paginator');
            $pagination = $paginator->paginate(
                $em->getRepository('AppBundle:Observation')->findByAuthor($user), /* query NOT result */
                $page/*page number*/,
                25/*limit per page*/
            );
            return $this->render('AppBundle:Front:viewMyObservation.html.twig', array(
                'page' => $page,
                'pagination' => $pagination));
        }
        else {
                throw new NotFoundHttpException("La page demandée n'existe pas");
        }

    }

    /**
     * @Route("/deleteobservation/{id}", name="delete_my_observation")
     * @param Observation $observation
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function deleteMyObservationAction(Observation $observation, Request $request)
    {
        // On récupère l'utilisateur courant
        $user = $this->getUser();

        // On vérifie que l'utilisateur courant est bien l'auteur de l'observation
        if ($user == $observation->getAuthor()) {
            $em = $this->getDoctrine()->getManager();
            $form = $this->get('form.factory')->create();
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $em->remove($observation);
                $em->flush();
                $request->getSession()->getFlashBag()->add('success', "L'observation a bien été supprimée.");
                return $this->redirectToRoute('view_my_observation', array('page' => 1 ));
            }

            return $this->render('AppBundle:Front:confirmDelObservation.html.twig', array(
                'observation' => $observation,
                'form' => $form->createView(),
            ));
        }
        else {
            throw new NotFoundHttpException("La page demandée n'existe pas");
        }
    }

    /**
     * @Route("/editmyobservation/{id}", name="edit_my_observation")
     * @param Observation $observation
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function editMyObservationAction(Observation $observation, Request $request)
    {
        // On récupère l'utilisateur courant
        $user = $this->getUser();

        // On vérifie que l'utilisateur courant est bien l'auteur de l'observation
        if ($user == $observation->getAuthor()) {
            $em = $this->getDoctrine()->getManager();
            $form = $this->get('form.factory')->create(ObservationFrontType::class, $observation);
            $oldImage = $observation->getImage();
            $form->handleRequest($request);
            if (null === $observation->getImage()) {
                $observation->setImage($oldImage->getFilename());
            }
            if ($form->isSubmitted() && $form->isValid()) {
                $observation->setStatus($this->getParameter('var_project')['status_obs_waiting']);
                $observation->setRejectMessage(null);
                $em->flush();
                $request->getSession()->getFlashBag()->add('success', "L'observation a bien été modifiée, elle sera ré-étudiée afin d'être validée .");
                return $this->redirectToRoute('view_my_observation', array('page' => 1 ));
            }
            return $this->render('AppBundle:Front:editMyObservation.html.twig', array(
                'observation' => $observation,
                'form' => $form->createView(),
            ));
        }
        else {
            throw new NotFoundHttpException("La page demandée n'existe pas");
        }
    }

    /**
     *
     * @Route("/contact", name="modal_contact")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @Method({"GET", "POST"})
     *
     */
    public function modalContactAction(Request $request)
    {
        $contact = new Contact();
        $form = $this->get('form.factory')->create(ContactType::class, $contact);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Affichage d'un message flash
            $request->getSession()->getFlashBag()->add('success', 'Votre message à bien été envoyé !');
            // Sauvegarder en Base de données
            $em = $this->getDoctrine()->getManager();
            $em->persist($contact);
            $em->flush();
            // Envoyer le mail
            $this->container->get('app.sendEmail')->sendEmailContact($contact);
            // Vider l'objet contact
            $contact = null;
            // Retour à la page d'accueil
            $em = $this->getDoctrine()->getManager();
            $listLastObservations = $em->getRepository('AppBundle:Observation')->findLastObservations(3);

            return $this->render('AppBundle:Front:index.html.twig', array(
                'listLastObservations' => $listLastObservations
            ));
        }
        return $this->render('AppBundle:Front:modalContact.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/sidebarsearch", name="side_bar_search")
     * @Method({"GET"})
     *
     */
    public function sideBarSearchAction()
    {
        return $this->render('AppBundle:Front:sideBarSearch.html.twig');
    }

    /**
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @param Request $request
     * @Route("/sidebarnewsletter", name="side_bar_newsletter")
     *
     */
    public function sideBarNewsletterAction(Request $request)
    {
        $emailNewsletter = new EmailNewsletter();
        // On crée le formulaire
        $form = $this->createForm(EmailNewsletterType::class, $emailNewsletter);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Affichage d'un message flash

            $request->getSession()->getFlashBag()->add('success', 'Vous êtes désormais inscrit à notre Newsletter');

            // Sauvegarder en Base de données
            $em = $this->getDoctrine()->getManager();
            $em->persist($emailNewsletter);
            $em->flush();

            // Retour à la page d'accueil
            $em = $this->getDoctrine()->getManager();
            $listLastObservations = $em->getRepository('AppBundle:Observation')->findLastObservations(3);

            return $this->render('AppBundle:Front:index.html.twig', array(
                'listLastObservations' => $listLastObservations,
                'form' => $form->createView()
            ));
        }
        /*return $this->render('AppBundle:Front:sideBarNewsletter.html.twig', array(
            'form' => $form->createView(),
        ));*/
    }

    /**
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/sidebarlast", name="side_bar_last")
     * @Method({"GET"})
     *
     */
    public function sideBarLastAction()
    {
        $em = $this->getDoctrine()->getManager();
        $listLastObservations = $em->getRepository('AppBundle:Observation')->findLastObservations(10);
        return $this->render('AppBundle:Front:sideBarLast.html.twig', array(
            'listLastObservations' => $listLastObservations
        ));
    }

    /**
     * @param $listobs
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/googlemap/{listobs}", name="google_map")
     * @Method({"GET"})
     *
     */
    public function googleMapAction($listobs)
    {
        $em = $this->getDoctrine()->getManager();
        if ($listobs instanceof Observation)
        {
            $query = $em->createQuery('SELECT c FROM AppBundle:Observation c WHERE c.id = ?1');
            $query->setParameter(1, $listobs->getId());
        }
        elseif ($listobs instanceof Taxrefv10)
        {
            $query = $em->createQuery('SELECT o, t FROM AppBundle:Observation o JOIN o.espece t WHERE t.id = ?1');
            $query->setParameter(1, $listobs->getId());
        }
        $listObservations = $query->getArrayResult();
        return $this->render('AppBundle:Front:googleMap.html.twig', array(
            'listObservations' => json_encode($listObservations, JSON_UNESCAPED_UNICODE)
        ));
    }
}
