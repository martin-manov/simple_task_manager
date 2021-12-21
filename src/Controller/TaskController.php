<?php

namespace App\Controller;

use App\Entity\Task;
use App\Form\TaskType;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/task")
 */
class TaskController extends AbstractController
{
    /**
     * @Route("/", name="tasks", methods={"GET"})
     */
    public function tasksAction(Request $request): Response
    {
        if ($user = $this->getUser()) {
            return $this->render('task/index.html.twig', ['tasks' => $user->getTasks()]);
        }

        throw new NotFoundHttpException('Invalid user');
    }

    /**
     * @Route("/form/{id}", name="task_form", methods={"GET", "POST"}, defaults={"id"=null})
     * 
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param TaskRepository $taskRepo
     * @param int|null $id
     * @return Response
     */
    public function taskFormAction(Request $request, EntityManagerInterface $em, TaskRepository $taskRepo, int $id = null): Response
    {
        if ($user = $this->getUser()) {
            
            if (!$task = $this->getValidTask($id)) {
                return $this->redirectToRoute('task_form');
            }
            
            $form = $this->createForm(TaskType::class, $task);
            $form->get('isCompleted')->setData(null !== $task->getDateCompleted());

            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $dateCompleted = $form['isCompleted']->getData() ? new \DateTime() : null;

                $task = $form->getData();
                $task->setDateCreated(new \DateTime());
                $task->setDateCompleted($dateCompleted);
                $task->setUser($user);

                $user->addTask($task);

                $em->persist($task);
                $em->persist($user);
                $em->flush();

                return $this->redirectToRoute('tasks');
            }

            return $this->render('task/form.html.twig', ['form' => $form->createView(), 'task' => $task]);
        }

        throw new NotFoundHttpException('Invalid user');
    }

    /**
     * @param integer|null $taskId
     * @return Task|null
     */
    private function getValidTask(int $taskId = null): ?Task
    {
        if (null !== $taskId) {
            $repo = $this->getDoctrine()->getRepository(Task::class);
            $task = $repo->find($taskId);

            return $task && $this->getUser() === $task->getUser() ? $task : null;
        }

        return new Task();
    }
}
