<?php

namespace Sogedial\SiteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Colis
 *
 * @ORM\Table(name="colis")
 * @ORM\Entity(repositoryClass="Sogedial\SiteBundle\Repository\ColisRepository")
 */
class Colis
{

    /**
     * @var string
     *
     * @ORM\Column(name="code_colis", type="string", length=11, nullable=false, unique=true)
     * @ORM\Id
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="palette", type="string", length=255, nullable=true)
     */
    private $palette;

    /**
     * @var string
     *
     * @ORM\Column(name="poidsBrutUVC", type="string", length=255, nullable=true)
     */
    private $poidsBrutUVC;

    /**
     * @var string
     *
     * @ORM\Column(name="poidsNetUVC", type="string", length=255, nullable=true)
     */
    private $poidsNetUVC;

    /**
     * @var string
     *
     * @ORM\Column(name="poidsBrutColis", type="string", length=255, nullable=true)
     */
    private $poidsBrutColis;

    /**
     * @var string
     *
     * @ORM\Column(name="volumeColis", type="string", length=255, nullable=true)
     */
    private $volumeColis;

    /**
     * @var string
     *
     * @ORM\Column(name="couchePalette", type="string", length=255, nullable=true)
     */
    private $couchePalette;

    /**
     * @var string
     *
     * @ORM\Column(name="coliscouche", type="string", length=255, nullable=true)
     */
    private $colisCouche;

    /**
     * @var string
     *
     * @ORM\Column(name="colispalette", type="string", length=255, nullable=true)
     */
    private $colisPalette;

    /**
     * @ORM\OneToOne(targetEntity="Sogedial\SiteBundle\Entity\Produit", inversedBy="colis", cascade={"persist"})
     * @ORM\JoinColumn(name="code_produit", nullable=true, referencedColumnName="code_produit")
     */
    private $produit;

    /**
     * @var string
     *
     * @ORM\Column(name="commercial_unity_number", type="string", length=64, nullable=true)
     */
    private $commercialUnityNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="commercial_unity_type", type="text", nullable=true)
     */
    private $commercialUnityType;

    /**
     * @var string
     *
     * @ORM\Column(name="commercial_unity_description", type="string", length=60, nullable=true)
     */
    private $commercialUnityDescription;

    /**
     * @var string
     *
     * @ORM\Column(name="gtin", type="string", length=255, nullable=true)
     */
    private $gtin;

    /**
     * @var string
     *
     * @ORM\Column(name="inferior_gtin", type="string", length=255, nullable=true)
     */
    private $inferiorGtin;

    /**
     * @var string
     *
     * @ORM\Column(name="number_of_product_by_gtin", type="string", length=255, nullable=true)
     */
    private $numberOfProductByGtin;

    /**
     * @var string
     *
     * @ORM\Column(name="number_of_gtin_different", type="string", length=255, nullable=true)
     */
    private $numberOfGtinDifferent;

    /**
     * @var string
     *
     * @ORM\Column(name="ean", type="string", length=60, nullable=true)
     */
    private $ean;

    /**
     * @var string
     *
     * @ORM\Column(name="type_format_ean_barre_code", type="text", nullable=true)
     */
    private $typeFormatEanBarreCode;

    /**
     * @var string
     *
     * @ORM\Column(name="net_weight_uc", type="string", length=55, nullable=true)
     */
    private $netWeightUc;

    /**
     * @var string
     *
     * @ORM\Column(name="net_weight_egouty_uc", type="string", length=55, nullable=true)
     */
    private $netWeightEgoutyUc;

    /**
     * @var string
     *
     * @ORM\Column(name="brut_weight_uc", type="string", length=55,nullable=true)
     */
    private $brutWeightUc;

    /**
     * @var string
     *
     * @ORM\Column(name="variable_weight_uc", type="string", length=55, nullable=true)
     */
    private $variableWeightUc;

    /**
     * @var string
     *
     * @ORM\Column(name="longer_dimensions_uc", type="string", length=55, nullable=true)
     */
    private $longerDimensionsUc;

    /**
     * @var string
     *
     * @ORM\Column(name="larger_dimensions_uc", type="string", length=55, nullable=true)
     */
    private $largerDimensionsUc;

    /**
     * @var string
     *
     * @ORM\Column(name="height_dimensions_uc", type="string", length=55, nullable=true)
     */
    private $heightDimensionsUc;

    /**
     * @var string
     *
     * @ORM\Column(name="volume_uc", type="string", length=55, nullable=true)
     */
    private $volumeUc;

    /**
     * @var string
     *
     * @ORM\Column(name="palette_type", type="text", nullable=true)
     */
    private $paletteType;

    /**
     * @var string
     *
     * @ORM\Column(name="couches_number_uc", type="string", length=55, nullable=true)
     */
    private $couchesNumberUc;

    /**
     * @var string
     *
     * @ORM\Column(name="number_of_uc_by_couche", type="string", length=10, nullable=true)
     */
    private $numberOfUcByCouche;

    /**
     * @var string
     *
     * @ORM\Column(name="number_of_products_by_uc", type="string", length=55, nullable=true)
     */
    private $numberOfProductsByUc;

    /**
     * @var string
     *
     * @ORM\Column(name="pcb_colis", type="string", length=60, nullable=true)
     */
    private $pcbColis;

    /**
     * @var string
     *
     * @ORM\Column(name="pieces_art_k", type="string", length=60, nullable=true)
     */
    private $piecesArtK;

    /**
     * @var Datetime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    private $createdAt;
    /**
     * @var Datetime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     */
    private $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime('now');
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * Set palette
     *
     * @param string $palette
     * @return Colis
     */
    public function setPalette($palette)
    {
        $this->palette = $palette;

        return $this;
    }

    /**
     * Get palette
     *
     * @return string
     */
    public function getPalette()
    {
        return $this->palette;
    }

    /**
     * Set poidsBrutUVC
     *
     * @param string $poidsBrutUVC
     * @return Colis
     */
    public function setPoidsBrutUVC($poidsBrutUVC)
    {
        $this->poidsBrutUVC = $poidsBrutUVC;

        return $this;
    }

    /**
     * Get poidsBrutUVC
     *
     * @return string
     */
    public function getPoidsBrutUVC()
    {
        return floatval(str_replace(",", ".", $this->poidsBrutUVC));
    }

    /**
     * Set poidsNetUVC
     *
     * @param string $poidsNetUVC
     * @return Colis
     */
    public function setPoidsNetUVC($poidsNetUVC)
    {
        $this->poidsNetUVC = $poidsNetUVC;

        return $this;
    }

    /**
     * Get poidsNetUVC
     *
     * @return string
     */
    public function getPoidsNetUVC()
    {
        return floatval(str_replace(",", ".", $this->poidsNetUVC));
    }

    /**
     * Set poidsBrutColis
     *
     * @param string $poidsBrutColis
     * @return Colis
     */
    public function setPoidsBrutColis($poidsBrutColis)
    {
        $this->poidsBrutColis = $poidsBrutColis;

        return $this;
    }

    /**
     * Get poidsBrutColis
     *
     * @return string
     */
    public function getPoidsBrutColis()
    {
        return $this->poidsBrutColis;
    }

    /**
     * Set volumeColis
     *
     * @param string $volumeColis
     * @return Colis
     */
    public function setVolumeColis($volumeColis)
    {
        $this->volumeColis = $volumeColis;

        return $this;
    }

    /**
     * Get volumeColis
     *
     * @return string
     */
    public function getVolumeColis()
    {

        return floatval(str_replace(",", ".", $this->volumeColis));
    }

    /**
     * Set couchePalette
     *
     * @param string $couchePalette
     * @return Colis
     */
    public function setCouchePalette($couchePalette)
    {
        $this->couchePalette = $couchePalette;

        return $this;
    }

    /**
     * Get couchePalette
     *
     * @return string
     */
    public function getCouchePalette()
    {
        return $this->couchePalette;
    }

    /**
     * Set produit
     *
     * @param \Sogedial\SiteBundle\Entity\Produit $produit
     * @return Colis
     */
    public function setProduit(\Sogedial\SiteBundle\Entity\Produit $produit = null)
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

    /**
     * Set colisCouche
     *
     * @param string $colisCouche
     * @return Colis
     */
    public function setColisCouche($colisCouche)
    {
        $this->colisCouche = $colisCouche;

        return $this;
    }

    /**
     * Get colisCouche
     *
     * @return string
     */
    public function getColisCouche()
    {
        return $this->colisCouche;
    }

    /**
     * Set colisPalette
     *
     * @param string $colisPalette
     * @return Colis
     */
    public function setColisPalette($colisPalette)
    {
        $this->colisPalette = $colisPalette;

        return $this;
    }

    /**
     * Get colisPalette
     *
     * @return string
     */
    public function getColisPalette()
    {
        return $this->colisPalette;
    }

    public function getRatio()
    {
        return $this->getVolumeColis() / str_replace(',', '.', $this->getPoidsBrutColis());
    }

    /**
     * @return string
     */
    public function getCommercialUnityNumber()
    {
        return $this->commercialUnityNumber;
    }

    /**
     * @param string $commercialUnityNumber
     */
    public function setCommercialUnityNumber($commercialUnityNumber)
    {
        $this->commercialUnityNumber = $commercialUnityNumber;
    }

    /**
     * @return string
     */
    public function getCommercialUnityType()
    {
        return $this->commercialUnityType;
    }

    /**
     * @param string $commercialUnityType
     */
    public function setCommercialUnityType($commercialUnityType)
    {
        $this->commercialUnityType = $commercialUnityType;
    }

    /**
     * @return string
     */
    public function getCommercialUnityDescription()
    {
        return $this->commercialUnityDescription;
    }

    /**
     * @param string $commercialUnityDescription
     */
    public function setCommercialUnityDescription($commercialUnityDescription)
    {
        $this->commercialUnityDescription = $commercialUnityDescription;
    }

    /**
     * @return string
     */
    public function getGtin()
    {
        return $this->gtin;
    }

    /**
     * @param string $gtin
     */
    public function setGtin($gtin)
    {
        $this->gtin = $gtin;
    }

    /**
     * @return string
     */
    public function getInferiorGtin()
    {
        return $this->inferiorGtin;
    }

    /**
     * @param string $inferiorGtin
     */
    public function setInferiorGtin($inferiorGtin)
    {
        $this->inferiorGtin = $inferiorGtin;
    }

    /**
     * @return string
     */
    public function getNumberOfProductByGtin()
    {
        return $this->numberOfProductByGtin;
    }

    /**
     * @param string $numberOfProductByGtin
     */
    public function setNumberOfProductByGtin($numberOfProductByGtin)
    {
        $this->numberOfProductByGtin = $numberOfProductByGtin;
    }

    /**
     * @return string
     */
    public function getNumberOfGtinDifferent()
    {
        return $this->numberOfGtinDifferent;
    }

    /**
     * @param string $numberOfGtinDifferent
     */
    public function setNumberOfGtinDifferent($numberOfGtinDifferent)
    {
        $this->numberOfGtinDifferent = $numberOfGtinDifferent;
    }

    /**
     * @return string
     */
    public function getEan()
    {
        return $this->ean;
    }

    /**
     * @param string $ean
     */
    public function setEan($ean)
    {
        $this->ean = $ean;
    }

    /**
     * @return string
     */
    public function getTypeFormatEanBarreCode()
    {
        return $this->typeFormatEanBarreCode;
    }

    /**
     * @param string $typeFormatEanBarreCode
     */
    public function setTypeFormatEanBarreCode($typeFormatEanBarreCode)
    {
        $this->typeFormatEanBarreCode = $typeFormatEanBarreCode;
    }

    /**
     * @return string
     */
    public function getNetWeightUc()
    {
        return $this->netWeightUc;
    }

    /**
     * @param string $netWeightUc
     */
    public function setNetWeightUc($netWeightUc)
    {
        $this->netWeightUc = $netWeightUc;
    }

    /**
     * @return string
     */
    public function getNetWeightEgoutyUc()
    {
        return $this->netWeightEgoutyUc;
    }

    /**
     * @param string $netWeightEgoutyUc
     */
    public function setNetWeightEgoutyUc($netWeightEgoutyUc)
    {
        $this->netWeightEgoutyUc = $netWeightEgoutyUc;
    }

    /**
     * @return string
     */
    public function getBrutWeightUc()
    {
        return $this->brutWeightUc;
    }

    /**
     * @param string $brutWeightUc
     */
    public function setBrutWeightUc($brutWeightUc)
    {
        $this->brutWeightUc = $brutWeightUc;
    }

    /**
     * @return string
     */
    public function getVariableWeightUc()
    {
        return $this->variableWeightUc;
    }

    /**
     * @param string $variableWeightUc
     */
    public function setVariableWeightUc($variableWeightUc)
    {
        $this->variableWeightUc = $variableWeightUc;
    }

    /**
     * @return string
     */
    public function getLongerDimensionsUc()
    {
        return $this->longerDimensionsUc;
    }

    /**
     * @param string $longerDimensionsUc
     */
    public function setLongerDimensionsUc($longerDimensionsUc)
    {
        $this->longerDimensionsUc = $longerDimensionsUc;
    }

    /**
     * @return string
     */
    public function getLargerDimensionsUc()
    {
        return $this->largerDimensionsUc;
    }

    /**
     * @param string $largerDimensionsUc
     */
    public function setLargerDimensionsUc($largerDimensionsUc)
    {
        $this->largerDimensionsUc = $largerDimensionsUc;
    }

    /**
     * @return string
     */
    public function getHeightDimensionsUc()
    {
        return $this->heightDimensionsUc;
    }

    /**
     * @param string $heightDimensionsUc
     */
    public function setHeightDimensionsUc($heightDimensionsUc)
    {
        $this->heightDimensionsUc = $heightDimensionsUc;
    }

    /**
     * @return string
     */
    public function getVolumeUc()
    {
        return $this->volumeUc;
    }

    /**
     * @param string $volumeUc
     */
    public function setVolumeUc($volumeUc)
    {
        $this->volumeUc = $volumeUc;
    }

    /**
     * @return string
     */
    public function getPaletteType()
    {
        return $this->paletteType;
    }

    /**
     * @param string $paletteType
     */
    public function setPaletteType($paletteType)
    {
        $this->paletteType = $paletteType;
    }

    /**
     * @return string
     */
    public function getCouchesNumberUc()
    {
        return $this->couchesNumberUc;
    }

    /**
     * @param string $couchesNumberUc
     */
    public function setCouchesNumberUc($couchesNumberUc)
    {
        $this->couchesNumberUc = $couchesNumberUc;
    }

    /**
     * @return string
     */
    public function getNumberOfUcByCouche()
    {
        return $this->numberOfUcByCouche;
    }

    /**
     * @param string $numberOfUcByCouche
     */
    public function setNumberOfUcByCouche($numberOfUcByCouche)
    {
        $this->numberOfUcByCouche = $numberOfUcByCouche;
    }

    /**
     * @return string
     */
    public function getNumberOfProductsByUc()
    {
        return $this->numberOfProductsByUc;
    }

    /**
     * @param string $numberOfProductsByUc
     */
    public function setNumberOfProductsByUc($numberOfProductsByUc)
    {
        $this->numberOfProductsByUc = $numberOfProductsByUc;
    }


    /**
     * @return string
     */
    public function getPcbColis()
    {
        return $this->pcbColis;
    }

    /**
     * @param string $pcbColis
     */
    public function setPcbColis($pcbColis)
    {
        $this->pcbColis = $pcbColis;
    }

    /**
     * @return string
     */
    public function getPiecesArtK()
    {
        return $this->piecesArtK;
    }

    /**
     * @param string $piecesArtK
     */
    public function setPiecesArtK($piecesArtK)
    {
        $this->piecesArtK = $piecesArtK;
    }

    /**
     * @return Datetime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param Datetime $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }
    /**
     * @return Datetime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param Datetime $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

}
