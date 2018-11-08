<?php

namespace Sogedial\SiteBundle\Service;

use Doctrine\ORM\EntityManager;
use Sogedial\SiteBundle\Entity\Assortiment;

class AssortimentService {
    public function __construct(EntityManager $entityManager, MultiSiteService $multisiteService) {
        // Entity manager
        $this->em = $entityManager;

        // Services
        $this->ms = $multisiteService;

        // Repositories
        $this->assortimentRepository = $this->em->getRepository('SogedialSiteBundle:Assortiment');
        $this->clientRepository = $this->em->getRepository('SogedialSiteBundle:Client');
        $this->entrepriseRepository = $this->em->getRepository('SogedialSiteBundle:Entreprise');
        $this->familleRepository = $this->em->getRepository('SogedialSiteBundle:Famille');
        $this->produitRepository = $this->em->getRepository('SogedialSiteBundle:Produit');
        $this->rayonRepository = $this->em->getRepository('SogedialSiteBundle:Rayon');
        $this->regionRepository = $this->em->getRepository('SogedialSiteBundle:Region');
        $this->secteurRepository = $this->em->getRepository('SogedialSiteBundle:Secteur');
        $this->sousFamilleRepository = $this->em->getRepository('SogedialSiteBundle:SousFamille');

        // Attributes
        $this->region = $this->ms->getRegion();
        $this->societe = $this->ms->getSociete();
        $this->nestedTypesMap = [
            3 => 'secteur',
            2 => 'rayon',
            1 => 'famille',
            0 => 'produit',
        ];
        $this->currentClient = null;
    }

    /**
     * Generates a Jstree-typed tree of the assortiment for a given prospect, containing all products as well as their categories.
     *
     * @param string $codeProspect Id of the prospect client.
     * @return object[] Jstree-formatted array of objects containing the whole assortiment based on the default assortiment.
     */
    public function generateJstreeAssortiment($codeProspect, $valeurAssortiment = null) {
        $baseProducts = $this->getAllProductsFromBaseAssortiment($codeProspect);
        $prospectProducts = $this->getAllProductCodesFromProspectAssortiment($codeProspect, $valeurAssortiment);
        $jstree = [];
        $this->buildJstree($jstree, $baseProducts, $prospectProducts);
        return $jstree;
    }

    /**
     * Generates a Jstree-typed tree of the assortiment for a given prospect, containing all products as well as their categories.
     *
     * @param string $codeProspect Id of the prospect client.
     * @return object[] Jstree-formatted array of objects containing the whole assortiment based on the default assortiment.
     */
    public function getJstreeAssortiment($codeProspect, $codeAssortiment = null) {
        $baseProducts = $this->getAllProductsFromBaseAssortiment($codeProspect);

        $prospectProducts = $this->getAllProductCodesFromProspectAssortiment($codeProspect, $codeAssortiment);
        $jstree = [];
        $this->buildJstree($jstree, $baseProducts, $prospectProducts);
        return $jstree;
    }

    /**
     * Fetches all products contained within an assortiment as well as some additional info on their secteur, rayon and famille.
     * The corresponding assortiment is the default assortiment associated to an enseigne and an entreprise, which is retrieved thanks to the prospect code.
     *
     * @param string $codeProspect Prospect ID.
     * @return array List of all products and additional info contained within the base assortiment.
     */
    private function getAllProductsFromBaseAssortiment($codeProspect) {
        $client = $this->clientRepository->find($codeProspect);
        $codeEntreprise = $client->getEntreprise()->getCode();
        $codeEnseigne = $client->getEnseigne()->getCode();
        $baseValeurAssortiment = $this->getBaseAssortimentValeur($codeProspect);
        $baseProducts = $this->assortimentRepository->getAllProductsNestingFromValeurAndEntreprise($baseValeurAssortiment, $codeEntreprise, $codeEnseigne);
        return $baseProducts;
    }

    /**
     * Finds the base assortiment of an enseigne and entreprise that are the same as the client's, and returns the base assortiment's value.
     *
     * @param string $codeClient Client ID.
     * @return string Value of the client's base assortiment.
     */
    private function getBaseAssortimentValeur($codeClient = null) {
        if (is_null($codeClient)) {
            $codeClient = $this->currentClient;
        }
        $client = $this->clientRepository->find($codeClient);
        $codeEnseigne = $client->getEnseigne()->getCode();
        $codeEntreprise = $client->getEntreprise()->getCode();
        $firstClientFromEnseigneAndEntreprise = $this->clientRepository->getFirstClientFromEnseigneAndEntreprise($codeEnseigne, $codeEntreprise);
        return $this->ms->getBaseAssortimentValeur($firstClientFromEnseigneAndEntreprise);
    }

    /**
     * Fetches all product codes contained within a prospect's assortiment.
     *
     * @param string $codeProspect Prospect ID.
     * @return array List of all product codes contained within the prospect's assortiment.
     */
    private function getAllProductCodesFromProspectAssortiment($codeProspect, $valeurAssortiment) {
        $prospect = $this->clientRepository->find($codeProspect);
        if ($valeurAssortiment == null){
            $valeurAssortiment = $this->ms->getAssortimentValeur($prospect);
        }
        if (is_null($valeurAssortiment)) {
            return null;
        } else {
            $codeEntreprise = $prospect->getEntreprise()->getCode();
            $prospectProducts = $this->assortimentRepository->getAllProductCodesFromValeurAndEntreprise($codeEntreprise, $valeurAssortiment);
            return $prospectProducts;
        }
    }

    private function getProductNumberInProspectAssortiment($codeProspect, $codeEntreprise, $isProspect){
        $numberOfProducts = $this->assortimentRepository->getProductNumberFromValeurAndEntreprise($codeProspect,$codeEntreprise,$isProspect);
        return $numberOfProducts;
    }

    /**
     * Generates a Jstree-compliant array of objects based on the given products.
     *
     * @param object[] $jstree Reference to the Jstree array.
     * @param array $baseProducts List of products, generally extracted from an assortiment.
     * @param array|null $prospectProducts List of product codes contained within the prospect's assortiment.
     * @return void
     */
    private function buildJstree(&$jstree, $baseProducts, $prospectProducts) {
        // Store all category ids along their local index in the parent category.
        $lookupTables = [
            'secteur' => [],
            'rayon' => [],
            'famille' => []
        ];

        // If a prospect assortiment was previously created, store the list of all product codes.
        if (is_null($prospectProducts)) {
            $prospectProductCodes = null;
        } else {
            $prospectProductCodes = array_reduce($prospectProducts, function ($acc, $prospectProduct) {
                $prospectProductCode = $prospectProduct['code_produit'];
                $acc[$prospectProductCode] = $prospectProductCode;
                return $acc;
            });
        }

        for ($i = 0, $l = count($baseProducts); $i < $l; $i++) {
            $this->handleProduct($baseProducts[$i], $jstree, $lookupTables, $prospectProductCodes);
        }
    }

    /**
     * Recursively parses the given product from top secteur to bottom product.
     * Push all of this product's related nodes into the passed tree and its lookup tables.
     *
     * @param array $product Product info containing ids and labels of itself and its parents.
     * @param object[] $jstree Reference to the Jstree array.
     * @param array[] $lookupTables Reference to the Jstree lookup tables.
     * @param array[]|null $prospectProductCodes Reference to the product codes contained within the prospect's assortiment.
     * @return void
     */
    private function handleProduct($product, &$jstree, &$lookupTables, &$prospectProductCodes) {
        if (is_null($product['code_secteur'])) {
            return;
        }
        $currentTypeId = 3;
        $nextParent = &$jstree;
        do {
            $nextParent = &$this->handleNestedType($product, $currentTypeId, $nextParent, $lookupTables, $prospectProductCodes);
            $currentTypeId = $currentTypeId - 1;
        } while ($currentTypeId >= 0);
    }

    /**
     * Converts each nested type of data (category or product itself) from a product into a Jstree node,
     * pushes it in the parent Jstree node and updates the Jstree lookup tables.
     *
     * @param array $product Product info containing ids and labels of itself and its parents.
     * @param integer $typeId Id of the current type being handled, referencing types defined in the nestedTypesMap class attribute.
     * @param array $parent Reference to the parent array containing all child Jstree node objects.
     * @param array[] $lookupTables Reference to the Jstree lookup tables.
     * @param array[]|null $prospectProductCodes Reference to the product codes contained within the prospect's assortiment.
     * @return void
     */
    private function &handleNestedType($product, $typeId, &$parent, &$lookupTables, &$prospectProductCodes) {
        $type = $this->nestedTypesMap[$typeId];
        if ($type === 'produit') {
            $productId = $product['code_' . $type];
            $productLibelle = $product['libelle_' . $type];
            $productMarque = $product['marque'];
            $productMarketingCode = $product['marketingCode'];
            $productPrixHt = $product['prixHt'];
            $productCodeTable = explode("-",$productId);
            $productCode = $productCodeTable[1];

            if (is_null($prospectProductCodes)) {
                $isProspectProductSelected = false;
            } else {
                $isProspectProductSelected = array_key_exists($productId, $prospectProductCodes);
                if ($isProspectProductSelected) {
                    unset($prospectProductCodes[$productId]);
                }
            }

            $productNode = [
                'id' => $productId,
                'state' => [
                    'selected' => $isProspectProductSelected,
                ],
                //'text' => sprintf('%s - %s - %s - %s €', $productLibelle, $productMarque, $productMarketingCode, $productPrixHt),
                'text' => $productLibelle,
                'type' => $type,
                'data' => [ "price" => $productPrixHt, "name" =>  $productLibelle, "code" =>  $productCode, "trademark" => $productMarque, "marketingCode" => $productMarketingCode ]
            ];
            array_push($parent, $productNode);
            return $parent;
        } else {
            // Search the type's lookup table to see if the current category already exists in the tree.
            $categoryId = $product['code_' . $type];

            if (isset($lookupTables[$type][$categoryId])) {
                $categoryIndex = $lookupTables[$type][$categoryId];
            } else {
                $categoryLibelle = $product['libelle_' . $type];
                $categoryNode = [
                    'id' => $categoryId,
                    'text' => $categoryLibelle,
                    'type' => $type,
                    'children' => [],
                ];
                $categoryIndex = count($parent);
                // Add the category node to the tree, inside the parent's children array.
                array_push($parent, $categoryNode);
                // Then, add the category node's local index to the lookup table.
                $lookupTables[$type][$categoryId] = $categoryIndex;
            }
            return $parent[$categoryIndex]['children'];
        }
    }


    /**
     * Generates an assortiment based on jstree nodes.
     *
     * @param object[] $nodes Array of node objects, each containing an id and a type keys.
     * @param string $codeClient Client ID.
     * @param boolean $isProspect Asserts whether the client is a prospect.
     * @param string $valeur Old assortiment value to be optionally kept when creating a new assortiment.
     * @return string[] Assortiment array containing string ids of all products matched from jstree nodes.
     */
    public function generateAssortiment($nodes, $codeClient, $isProspect = false, $valeur = null) {
        $this->currentClient = $codeClient;
        $assortiment = [];
        $this->populateProductIdsInAssortimentFromNodes($nodes, $assortiment);

        // If no product could be found, prevent the creation of the assortiment and return an exception.
        if (count($assortiment) === 0) {
            throw new \Exception("Aucun produit n'a été trouvé dans l'assortiment choisi. Veuillez modifier votre sélection.");
        }

        if($valeur === null){
            $assortimentValeur = $this->generateRandomProspectAssortimentValeur();
        } else {
            $assortimentValeur = $valeur;
        }

        for ($i = 0, $l = count($assortiment); $i < $l; $i++) {
            $this->persistProductToAssortiment($assortiment[$i], $assortimentValeur);
        }
        $this->em->flush();
        return $this->buildCodeAssortiment($assortimentValeur, $assortiment[0]);
    }

    /**
     * Recursively parses the given nodes until there are only products left.
     * Push these products' codes into the passed assortiment.
     *
     * @param object[] $node Array of node objects, each containing an id and a type keys.
     * @param string[] $assortiment Reference to the assortiment array containing string ids of all products matched from jstree nodes.
     */
    private function populateProductIdsInAssortimentFromNodes($nodes, &$assortiment) {
        for ($i = 0, $length = count($nodes); $i < $length; $i++) {
            $this->handleAssortimentNode($nodes[$i], $assortiment);
        }
    }

    /**
     * Recursively parses the given nodes until there are only products left.
     * Push these products' codes into the passed assortiment.
     *
     * @param object $node Jstree node object containing an id and a type keys.
     * @param string[] $assortiment Reference to the assortiment array containing string ids of all products matched from jstree nodes.
     * @return void
     */
    private function handleAssortimentNode($node, &$assortiment) {
         $nodeId = $node['id'];
        $nodeType = $node['type'];

        // If the node is of type 'product', directly register its id to the assortiment.
        if ($nodeType === 'produit') {
            array_push($assortiment, $nodeId);
        } else {
            // Otherwise, recursively search for the underlying products contained in the given category (secteur/rayon/famille/sous-famille).
            list($children, $childrenType) = $this->getChildrenFromParentIdAndType($nodeId, $nodeType);
            $childNodes = $this->mapDatabaseObjectsToNodeFormat($children, $childrenType, 'minimal');
            $this->populateProductIdsInAssortimentFromNodes($childNodes, $assortiment);
        }
    }

    /**
     * Organizes database-fetched data into a jstree node-like format.
     *
     * @param object[] $databaseObjects Objects containing node information fetched from the database.
     * @param string $nodesType Type of underlying nodes.
     * @param string $subset Optional subset name to avoid returning useless data.
     * @return array Jstree node-like structured objects.
     */
    public function mapDatabaseObjectsToNodeFormat($databaseObjects, $nodesType, $subset = 'full') {
        return array_map(function ($databaseObject) use ($nodesType, $subset) {
            if ($subset === 'full') {
                return [
                    'id' => $databaseObject['code'],
                    'text' => $databaseObject['libelle'],
                    'type' => $nodesType,
                    'children' => ($nodesType !== 'produit'),
                ];
            } elseif ($subset === 'minimal') {
                return [
                    'id' => $databaseObject['code'],
                    'type' => $nodesType,
                ];
            }
        }, $databaseObjects);
    }

    /*
        The following function usually applies for a top-down (lazy-loading) approach to creating an assortiment.
        This approach is mostly useful when creating an assortiment from scratch, not really for creating an assortiment from another assortiment.
        Nonetheless, to do the latter, the produits_compteur table could be used in order to lazy-load nodes of particular categories and types.
        Although it could potentially grant faster loading times, this limits and complicates the possibilities for tree-searching and editing features.
    */

    /**
     * Fetches child database objects and their type from the id and the type of a parent node.
     *
     * @param string $parentId Id of the parent node.
     * @param string $parentType Type of the parent node.
     * @return array Child database objects and their type.
     */
    public function getChildrenFromParentIdAndType($parentId, $parentType) {
        switch ($parentType) {
            case '#':
            case 'undefined':
                $children = $this->secteurRepository->getAllSecteursOfRegion($this->region);
                $childrenType = 'secteur';
                break;
            case 'secteur':
                $children = $this->rayonRepository->getAllRayonsOfSecteur($parentId);
                $childrenType = 'rayon';
                break;
            case 'rayon':
                $children = $this->familleRepository->getAllFamillesOfRayon($parentId);
                $childrenType = 'famille';
                break;
            case 'famille':
                $children = $this->sousFamilleRepository->getAllSousFamillesOfFamille($parentId);
                $childrenType = 'sousFamille';
                break;
            case 'sousFamille':
                $baseAssortimentValeur = $this->getBaseAssortimentValeur();
                $children = $this->produitRepository->getAllProductsOfSousFamilleAndEntrepriseInAssortiment($parentId, $this->societe, $baseAssortimentValeur);
                $childrenType = 'produit';
                break;
            default:
                break;
        }
        return [
            $children,
            $childrenType,
        ];
    }

    /**
     * Generates a random, unique assortiment 'valeur' for a prospect client.
     *
     * @return string Unique assortiment 'valeur'.
     */
    public function generateRandomProspectAssortimentValeur() {
        //do {
            $rand = rand(10000000, 99999999);
            $valeur = 'PRP'.$rand;
            //$assortiment = $this->assortimentRepository->findOneByValeur($valeur);
        //} while($assortiment != null);
        return $valeur;
    }

    /**
     * Persists an assortiment with all necessary values (without flushing it).
     *
     * @param string $productId Id of the product to add to the assortiment.
     * @param string $assortimentValeur Value of the global assortiment in which to add the product(s).
     * @return void
     */
    private function persistProductToAssortiment($productId, $assortimentValeur) {
        // Get all necessary data.
        $assortimentCode = $this->buildCodeAssortiment($assortimentValeur, $productId);
        $product = $this->produitRepository->find($productId);
        $region = $this->regionRepository->find($this->region);
        $societe = $this->entrepriseRepository->find($this->societe);

        // Build the assortiment from previous data.
        $assortiment = new Assortiment();
        $assortiment->setCode($assortimentCode);
        $assortiment->setProduit($product);
        $assortiment->setRegion($region);
        $assortiment->setEntreprise($societe);
        $assortiment->setValeur($assortimentValeur);
        $this->em->persist($assortiment);
    }

    /**
     * Builds the correct code assortiment structure from the global 'valeur' and the id of the product which will be added to the assortiment.
     *
     * @param string $assortimentValeur Value of the global assortiment in which to add the product(s).
     * @param string $productId Id of the product to add to the assortiment.
     * @return void
     */
    private function buildCodeAssortiment($assortimentValeur, $productId) {
        $pluckedProductCode = $this->getProductCodeWithoutCodeSociete($productId);
        return $this->societe.'-'.$assortimentValeur.$pluckedProductCode;
    }

    /**
     * Plucks the entreprise code from the whole product id, leaving only the barebone product code.
     *
     * @param string $productId Id of the product to add to the assortiment.
     * @return void
     */
    private function getProductCodeWithoutCodeSociete($productId) {
        return explode('-', $productId)[1];
    }
}
