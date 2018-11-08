<?php

namespace Sogedial\SiteBundle\Command;

use Sogedial\SiteBundle\Entity\MailParams;
use Sogedial\SiteBundle\Entity\ProduitCompteur;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Sogedial\SiteBundle\Command\ImporterCommands;

class SetupDefaultMailParamsCommand extends ImporterManager
{
    protected function configure()
    {
        parent::configureCmd(
            'sogedial:setupDefaultMailParams',
            'Setup constant tables mailparams'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $now = new \DateTime();
        $output->writeln('<comment>Start (default mail params) : ' . $now->format('d-m-Y G:i:s') . ' ---</comment>');

        $this->importMailParams($input, $output);

        $this->setup($input, $output, 0, "default mail params");
        $this->setSkipped(-1);

        $now = new \DateTime();
        $output->writeln('<comment>End (default mail params) : ' . $now->format('d-m-Y G:i:s') . ' ---</comment>');
    }

    protected function importMailParams(InputInterface $input, OutputInterface $output)
    {
        $data = $this->getDefaultMailParams($input, $output);

        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        $size = count($data);
        $batchSize = 20;
        $i = 1;

        $progress = new ProgressBar($output, $size);
        $progress->start();

        foreach ($data as $row) {
            $mailParams = $em->getRepository('SogedialSiteBundle:MailParams')
                ->findOneBy(array('type' => $row[1], 'entreprise' => $row[6]));
            $entreprise = $em->getRepository('SogedialSiteBundle:Entreprise')->findOneBy(array('code' => $row[6]));
                
            if (!($mailParams instanceof MailParams)) {

                $mailParams = new MailParams();
                $mailParams->setType($row[0]);
                $mailParams->setMailCc($row[1]);
                $mailParams->setFrom($row[2]);
                $mailParams->setTo($row[3]);
                $mailParams->setObject($row[4]);
                $mailParams->setTemplate($row[5]);
                $mailParams->setEntreprise($entreprise);                
            }
            //On evite de le mettre à jour car les infos sont surement plus à jour.


            $em->persist($mailParams);

            if (($i % $batchSize) === 0) {
                $em->flush();
                $em->clear();

                $progress->advance($batchSize);

                $now = new \DateTime();
                $output->writeln(' of etatCommande imported ... | ' . $now->format('d-m-Y G:i:s'));
            }
            $i++;
        }

        $em->flush();
        $em->clear();

        $progress->finish();
        $output->writeln('');

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return mixed
     */
    protected function getDefaultMailParams(InputInterface $input, OutputInterface $output)
    {
        $data = array();

        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        $entreprises = $em->getRepository('SogedialSiteBundle:Entreprise')->findAll();

        foreach ($entreprises as $key => $entreprise) {
            //Mail pour franco
            $data[] = array(
                "FRANCO_MAIL",
                ["sekou.koita@groupesafo.com", "ridha.bensaber@groupesafo.com"],
                "no-reply@catalogue.sofridis.com",
                "admin@".$entreprise->getNomEnvironnement().".com",
                "[E-catalogue] - Notification de commande",
                "SogedialIntegrationBundle:Email:franco-email-template.html.twig",
                $entreprise->getCode()
            );

            $data[] = array(
                "STOCK_ENGAGEMENT",
                ["sekou.koita@groupesafo.com", "ridha.bensaber@groupesafo.com"],
                "no-reply@catalogue.sofridis.com",
                "admin@".$entreprise->getNomEnvironnement().".com",
                "[E-catalogue] - Demande de stock engagement",
                "SogedialIntegrationBundle:Email:promotion-engagement-demande.html.twig",
                $entreprise->getCode()
            );

            $data[] = array(
                "REFERENCEMENT_METI",
                ["sekou.koita@groupesafo.com", "ridha.bensaber@groupesafo.com"],
                "no-reply@catalogue.sofridis.com",
                "admin@".$entreprise->getNomEnvironnement().".com",
                "[E-catalogue] - Demande de référencement méti",
                "SogedialIntegrationBundle:Email:produit-meti-referencement-demande.html.twig",
                $entreprise->getCode()
            );
        }
        return $data;
    }

}
