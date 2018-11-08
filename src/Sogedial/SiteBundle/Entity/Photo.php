<?php

namespace Sogedial\SiteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sogedial\SiteBundle\Model\ImageManipulator;

/**
 * Photo
 *
 * @ORM\Table(name="photo")
 * @ORM\Entity(repositoryClass="Sogedial\SiteBundle\Repository\PhotoRepository")
 */
class Photo
{

    /**
     * @var integer
     *
     * @ORM\Column(name="id_photo", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="nom_photo", type="string", length=255)
     */
    private $libelle;

    /**
     * @var integer
     *
     * @ORM\Column(name="hauteur_photo", type="integer", nullable=true)
     */
    private $hauteur;

    /**
     * @var integer
     *
     * @ORM\Column(name="largeur_photo", type="integer", nullable=true)
     */
    private $largeur;

    /**
     * @var integer
     *
     * @ORM\Column(name="poids_photo", type="integer", nullable=true)
     */
    private $poids;

    /**
     * @var string
     *
     * @ORM\Column(name="source_photo", type="string", length=255)
     */
    private $source;

    /**
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Produit", inversedBy="photos",cascade={"persist"})
     * @ORM\JoinColumn(nullable=false, name="code_produit", referencedColumnName="code_produit")
     */
    private $produit;

    /**
     * @var boolean
     *
     * @ORM\Column(name="display", type="boolean", nullable=true, options={"default" = 0})
     */
    private $display;

    /**
     * @var boolean
     *
     * @ORM\Column(name="cover", type="boolean", nullable=true, options={"default" = 0})
     */
    private $cover;

    /**
     * @var string
     *
     * @ORM\Column(name="reference", type="string", nullable=true, length=255)
     */
    private $reference;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="text", nullable=true)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="argument", type="text", nullable=true)
     */
    private $argument;

    /**
     * @var string
     *
     * @ORM\Column(name="indiactor_21", type="string", nullable=true, length=1)
     */
    private $indicator21;

    /**
     * @var string
     *
     * @ORM\Column(name="indiactor_22", type="string", nullable=true, length=1)
     */
    private $indicator22;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set libelle
     *
     * @param string $libelle
     * @return Photo
     */
    public function setLibelle($libelle)
    {
        $this->libelle = $libelle;

        return $this;
    }

    /**
     * Get libelle
     *
     * @return string
     */
    public function getLibelle()
    {
        return $this->libelle;
    }

    /**
     * Set hauteur
     *
     * @param integer $hauteur
     * @return Photo
     */
    public function setHauteur($hauteur)
    {
        $this->hauteur = $hauteur;

        return $this;
    }

    /**
     * Get hauteur
     *
     * @return integer
     */
    public function getHauteur()
    {
        return $this->hauteur;
    }

    /**
     * Set largeur
     *
     * @param integer $largeur
     * @return Photo
     */
    public function setLargeur($largeur)
    {
        $this->largeur = $largeur;

        return $this;
    }

    /**
     * Get largeur
     *
     * @return integer
     */
    public function getLargeur()
    {
        return $this->largeur;
    }

    /**
     * Set poids
     *
     * @param integer $poids
     * @return Photo
     */
    public function setPoids($poids)
    {
        $this->poids = $poids;

        return $this;
    }

    /**
     * Get poids
     *
     * @return integer
     */
    public function getPoids()
    {
        return $this->poids;
    }

    /**
     * Set source
     *
     * @param string $source
     * @return Photo
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get source
     *
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Get source
     *
     * @return string
     */
    public function getSourceCropped()
    {
        return $this->source;
    }

    /**
     * Set produit
     *
     * @param \Sogedial\SiteBundle\Entity\Produit $produit
     * @return Photo
     */
    public function setProduit(\Sogedial\SiteBundle\Entity\Produit $produit)
    {
        $this->produit = $produit;

        return $this;
    }

    /**
     * Get produit
     *
     * @return \Sogedial\SiteBundle\Entity\Produit
     */
    public function getProduit()
    {
        return $this->produit;
    }

    public function resize($x1, $x2, $y1, $y2, $base_path, $ean = null)
    {
        // array of valid extensions
        $validExtensions = array('.jpg', '.jpeg', '.gif', '.png');
        // get extension of the uploaded file
        $fileExtension = strrchr($this->getSource(), ".");
        // check if file Extension is on the list of allowed ones
        if (in_array(strtolower($fileExtension), $validExtensions)) {
            $manipulator = new ImageManipulator($base_path . 'original/' . $this->getSource());

            $newImage = $manipulator->crop($x1, $y1, $x2, $y2);
            $manipulator->resample('430', '277');
            $manipulator->save($base_path . 'resize/' . (($ean) ? $ean . '/' : '') . $this->getSource());
            return true;
        }
        return false;
    }

    public function deleteFile($base_path)
    {
        if (file_exists($base_path . 'original/' . $this->produit->getEan13() . '/' . $this->getSource())) {
            unlink($base_path . 'original/' . $this->produit->getEan13() . '/' . $this->getSource());
        }
        if (file_exists($base_path . 'resize/' . $this->produit->getEan13() . '/' . $this->getSource())) {
            unlink($base_path . 'resize/' . $this->produit->getEan13() . '/' . $this->getSource());
        }
    }

    public function isCropped($base_path)
    {
        return file_exists('./' . $base_path . 'resize/' . $this->produit->getEan13() . '/' . $this->getSource());
    }

    /**
     * Set display
     *
     * @param boolean $display
     * @return Photo
     */
    public function setDisplay($display)
    {
        $this->display = $display;

        return $this;
    }

    /**
     * Get display
     *
     * @return boolean
     */
    public function getDisplay()
    {
        return $this->display;
    }

    /**
     * Set cover
     *
     * @param boolean $cover
     * @return Photo
     */
    public function setCover($cover)
    {
        $this->cover = $cover;

        return $this;
    }

    /**
     * Get cover
     *
     * @return boolean
     */
    public function getCover()
    {
        return $this->cover;
    }

    /**
     * @return string
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * @param string $reference
     */
    public function setReference($reference)
    {
        $this->reference = $reference;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getArgument()
    {
        return $this->argument;
    }

    /**
     * @param string $argument
     */
    public function setArgument($argument)
    {
        $this->argument = $argument;
    }

    /**
     * @return string
     */
    public function getIndicator21()
    {
        return $this->indicator21;
    }

    /**
     * @param string $indicator21
     */
    public function setIndicator21($indicator21)
    {
        $this->indicator21 = $indicator21;
    }

    /**
     * @return string
     */
    public function getIndicator22()
    {
        return $this->indicator22;
    }

    /**
     * @param string $indicator22
     */
    public function setIndicator22($indicator22)
    {
        $this->indicator22 = $indicator22;
    }

}
