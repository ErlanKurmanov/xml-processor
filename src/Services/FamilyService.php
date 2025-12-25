<?php
require_once __DIR__ . '/RealEstateService.php';
require_once __DIR__ . '/../Database.php';

class FamilyService {
    private Database $db;
    private RealEstateService $reService;

    public function __construct() {
        $this->db = new Database();
        $this->reService = new RealEstateService();
    }

    public function getMember(int $id) {
        $stmt = $this->db->getPdo()->prepare("SELECT * FROM family_members WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getMemberRealEstate(int $id) {
        $stmt = $this->db->getPdo()->prepare("SELECT * FROM real_estate WHERE member_id = ?");
        $stmt->execute([$id]);
        return $stmt->fetchAll();
    }

    public function createTask(string $xmlContent): int {
        $stmt = $this->db->getPdo()->prepare("INSERT INTO tasks (input_file, status) VALUES (?, 'PENDING') RETURNING id");
        $stmt->execute([$xmlContent]);
        return $stmt->fetchColumn();
    }

    public function getTask(int $taskId) {
        $stmt = $this->db->getPdo()->prepare("SELECT * FROM tasks WHERE id = ?");
        $stmt->execute([$taskId]);
        return $stmt->fetch();
    }

    public function processLogic(string $xmlString, ?int $taskId = null): string {
        $pdo = $this->db->getPdo();

        try {
            $xml = simplexml_load_string($xmlString);
            if ($xml === false) throw new Exception("Invalid XML");

            $pdo->beginTransaction();
            $results = [];

            foreach ($xml->Member as $node) {
                $data = [
                    'ln' => (string)$node->LastName,
                    'fn' => (string)$node->FirstName,
                    'mn' => (string)$node->MiddleName,
                    'bd' => (string)$node->BirthDate,
                    'rel' => (string)$node->Relation,
                    'app' => isset($node->IsApplicant) && (string)$node->IsApplicant == 'true'
                ];

                $stmt = $pdo->prepare("
                    INSERT INTO family_members (last_name, first_name, middle_name, birth_date, relation, is_applicant, task_id)
                    VALUES (:ln, :fn, :mn, :bd, :rel, :app, :tid)
                    ON CONFLICT (last_name, first_name, middle_name, birth_date) 
                    DO UPDATE SET relation = EXCLUDED.relation
                    RETURNING id
                ");

                $stmt->execute([
                    ':ln' => $data['ln'], ':fn' => $data['fn'], ':mn' => $data['mn'],
                    ':bd' => $data['bd'], ':rel' => $data['rel'],
                    ':app' => $data['app'] ? 'true' : 'false',
                    ':tid' => $taskId
                ]);
                $memberId = $stmt->fetchColumn();

                $reInfo = $this->reService->getByMember($data);

                if ($reInfo['hasRealEstate']) {
                    foreach ($reInfo['objects'] as $obj) {
                        $reStmt = $pdo->prepare("INSERT INTO real_estate (member_id, type, address, ownership) VALUES (?, ?, ?, ?)");
                        $reStmt->execute([$memberId, $obj['Type'], $obj['Address'], $obj['Ownership']]);
                    }
                }

                $results[] = ['member' => $data, 're' => $reInfo];
            }

            $pdo->commit();
            return $this->generateXml($results);

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    private function generateXml(array $data): string {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        $root = $dom->createElement('FamilyRealEstateResult');
        $dom->appendChild($root);

        foreach ($data as $item) {
            $m = $item['member'];
            $re = $item['re'];

            $memNode = $dom->createElement('Member');
            $memNode->appendChild($dom->createElement('FIO', "{$m['ln']} {$m['fn']} {$m['mn']}"));
            $memNode->appendChild($dom->createElement('BirthDate', $m['bd']));
            $memNode->appendChild($dom->createElement('Relation', $m['rel']));

            $reNode = $dom->createElement('RealEstate');
            $reNode->appendChild($dom->createElement('HasRealEstate', $re['hasRealEstate'] ? 'true' : 'false'));

            $objsNode = $dom->createElement('Objects');
            foreach ($re['objects'] as $obj) {
                $oNode = $dom->createElement('Object');
                $oNode->appendChild($dom->createElement('Type', $obj['Type']));
                $oNode->appendChild($dom->createElement('Address', $obj['Address']));
                $oNode->appendChild($dom->createElement('Ownership', $obj['Ownership']));
                $objsNode->appendChild($oNode);
            }
            $reNode->appendChild($objsNode);
            $memNode->appendChild($reNode);
            $memNode->appendChild($dom->createElement('Status', 'OK'));
            $root->appendChild($memNode);
        }
        return $dom->saveXML();
    }
}