<?php

namespace App\Command;

use App\Entity\Transaction;
use App\Entity\User;
use App\Service\Twig;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class CommandPayMonth extends Command
{
    private $twig;
    private $mailer;
    private $manager;

    protected static $defaultName = 'payment:report';

    public function __construct(Twig $twig, MailerInterface $mailer, EntityManagerInterface $manager)
    {
        $this->twig = $twig;
        $this->mailer = $mailer;
        $this->manager = $manager;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'email_address',
                null,
                'Адрес пользователя',
                'artem@mail.ru'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Создаем пользователя по которому будем
        $user = $this->manager->getRepository(User::class)->findOneBy([
            'email' => $input->getArgument('email_address'),
        ]);

        // Транзакции за последний месяц с текущей даты
        $transactions = $this->manager->getRepository(Transaction::class)->findTransactionMonth($user);
        if ([] !== $transactions) {
            // Период создания отчета
            // Текущая дата
            $endDate = (new \DateTime())->format('Y-m-d');
            // Месяц назад
            $startDate = (new \DateTime())->modify('-1 month')->format('Y-m-d');

            // Найдем итоговую сумму за данный период
            $amount = 0;
            foreach ($transactions as $transaction) {
                $amount += $transaction['sum'];
            }

            // Шаблон сообщения
            $html = $this->twig->render(
                'mail/ReportPayMonth.html.twig',
                [
                    'transactions' => $transactions,
                    'total' => $amount,
                    'endDate' => $endDate,
                    'startDate' => $startDate,
                ]
            );

            // Формируем сообщение пользователю от администратора
            $message = (new Email())
                ->to($input->getArgument('email_address'))
                ->from('admin@mail.ru')
                ->subject('Отчет по данным об оплаченных курсах за месяц')
                ->html($html);

            try {
                // Отправка сообщения пользователю
                $this->mailer->send($message);
            } catch (TransportExceptionInterface $e) {
                $output->writeln($e->getMessage());

                $output->writeln('Возникла ошибка. Не удалось отправить сообщение');

                return Command::FAILURE;
            }
        }

        $output->writeln('Отчет успешно сформирован');

        return Command::SUCCESS;
    }
}
