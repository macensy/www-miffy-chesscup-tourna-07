<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../model/database.php';
require_once __DIR__ . '/../model/userModel.php';
require_once __DIR__ . '/../vendor/autoload.php';

class usermanager {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connectDB();
    }

    public function getUser() {
        return $this->db->query("SELECT * FROM tbl_players ORDER BY userID ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAdmins() {
        return $this->db->query("SELECT * FROM tbl_admins ORDER BY adminID ASC")->fetchAll(PDO::FETCH_ASSOC);
    }


    public function getLeaderboards() {
        $sql = "SELECT p.*,
                (SELECT IFNULL(SUM(p1_score), 0) FROM tbl_pairing WHERE player1_id = p.userID) +
                (SELECT IFNULL(SUM(p2_score), 0) FROM tbl_pairing WHERE player2_id = p.userID) as total_pts
                FROM tbl_players p
                ORDER BY total_pts DESC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPairings($round = 1) {
        try {
            $sql = "SELECT m.*, p1.firstName AS p1Name, p2.firstName AS p2Name
                    FROM tbl_pairing m
                    INNER JOIN tbl_players p1 ON m.player1_id = p1.userID
                    INNER JOIN tbl_players p2 ON m.player2_id = p2.userID
                    WHERE m.round_num = ?
                    ORDER BY m.match_id ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$round]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public function getPlayerWDL() {
        $sql = "SELECT
                    p.userID,
                    p.firstName,
                    SUM(CASE WHEN pair.winner_id = p.userID THEN 1 ELSE 0 END) AS wins,
                    SUM(CASE
                        WHEN pair.status = 'FINISHED' AND pair.winner_id IS NULL
                             AND (pair.player1_id = p.userID OR pair.player2_id = p.userID)
                        THEN 1 ELSE 0 END) AS draws,
                    SUM(CASE
                        WHEN pair.status = 'FINISHED' AND pair.winner_id IS NOT NULL
                             AND pair.winner_id != p.userID
                             AND (pair.player1_id = p.userID OR pair.player2_id = p.userID)
                        THEN 1 ELSE 0 END) AS losses
                FROM tbl_players p
                LEFT JOIN tbl_pairing pair
                    ON (pair.player1_id = p.userID OR pair.player2_id = p.userID)
                    AND pair.status = 'FINISHED'
                GROUP BY p.userID, p.firstName
                ORDER BY wins DESC, draws DESC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function generatePairings($round) {
        try {
            if ((int)$round > 7) return "Maximum of 7 rounds reached.";

            $check = $this->db->prepare("SELECT COUNT(*) FROM tbl_pairing WHERE round_num = ?");
            $check->execute([(int)$round]);
            if ($check->fetchColumn() > 0) return "Pairings for Round $round already exist.";

            if ((int)$round > 1) {
                $prevRound = (int)$round - 1;
                $pending = $this->db->prepare("SELECT COUNT(*) FROM tbl_pairing WHERE round_num = ? AND status != 'FINISHED'");
                $pending->execute([$prevRound]);
                if ($pending->fetchColumn() > 0) {
                    return "Round $prevRound is still ongoing. Finish all matches before generating Round $round.";
                }
            }

            $players = $this->db->query("SELECT userID FROM tbl_players ORDER BY RAND()")->fetchAll(PDO::FETCH_COLUMN);
            if (count($players) < 2) return "Not enough players to generate pairings.";

            $stmt = $this->db->prepare("INSERT INTO tbl_pairing (player1_id, player2_id, p1_score, p2_score, round_num, status) VALUES (?, ?, 0, 0, ?, 'PENDING')");
            for ($i = 0; $i + 1 < count($players); $i += 2) {
                $stmt->execute([$players[$i], $players[$i+1], (int)$round]);
            }
            return true;
        } catch (Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }

    public function resetTournament() {
        try {
            $this->db->exec("DELETE FROM tbl_pairing");
            $this->db->exec("UPDATE tbl_standings SET total_score = 0");
            return true;
        } catch (Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }

    public function getAdminDashboardStats($currentRound = 1) {
        $stats = [];
        $stats['registered_players'] = $this->db->query("SELECT COUNT(*) FROM tbl_players")->fetchColumn();

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM tbl_pairing WHERE round_num = ? AND status != 'FINISHED'");
        $stmt->execute([(int)$currentRound]);
        $stats['unfinished_matches'] = $stmt->fetchColumn();

        $stats['completed_rounds'] = $this->db->query(
            "SELECT COUNT(DISTINCT round_num) FROM tbl_pairing
             WHERE round_num NOT IN (SELECT DISTINCT round_num FROM tbl_pairing WHERE status != 'FINISHED')"
        )->fetchColumn();

        $stats['male_count']   = (int)$this->db->query("SELECT COUNT(*) FROM tbl_players WHERE gender = 'Male'")->fetchColumn();
        $stats['female_count'] = (int)$this->db->query("SELECT COUNT(*) FROM tbl_players WHERE gender = 'Female'")->fetchColumn();

        return $stats;
    }

    public function updateScoreFunc($mid, $s1, $s2) {
        $stmt = $this->db->prepare("SELECT player1_id, player2_id FROM tbl_pairing WHERE match_id = ?");
        $stmt->execute([(int)$mid]);
        $match = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$match) return "Error: Match not found.";

        $p1 = $match['player1_id'];
        $p2 = $match['player2_id'];

        $winner = null;
        if ($s1 > $s2) $winner = $p1;
        elseif ($s2 > $s1) $winner = $p2;

        $res = $this->db->prepare("UPDATE tbl_pairing SET p1_score = ?, p2_score = ?, winner_id = ?, status = 'FINISHED' WHERE match_id = ?")
                        ->execute([$s1, $s2, $winner, (int)$mid]);
        if (!$res) return "Error updating score.";

       
        foreach ([$p1, $p2] as $pid) {
            // Get player's name from tbl_players
            $nameStmt = $this->db->prepare("SELECT CONCAT(firstName, ' ', lastName) AS player_name FROM tbl_players WHERE userID = ?");
            $nameStmt->execute([(int)$pid]);
            $nameRow = $nameStmt->fetch(PDO::FETCH_ASSOC);
            $playerName = $nameRow ? $nameRow['player_name'] : '';

            $scoreStmt = $this->db->prepare(
                "SELECT COUNT(*) FROM tbl_pairing WHERE winner_id = ? AND status = 'FINISHED'"
            );
            $scoreStmt->execute([(int)$pid]);
            $totalScore = (int)$scoreStmt->fetchColumn();


            $upsert = $this->db->prepare(
                "INSERT INTO tbl_standings (player_id, player_name, total_score)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE player_name = VALUES(player_name), total_score = VALUES(total_score)"
            );
            $upsert->execute([(int)$pid, $playerName, $totalScore]);
        }

        return true;
    }

    public function updateUser($id, $fname, $lname, $age, $rating) {
        return $this->db->prepare("UPDATE tbl_players SET firstName=?, lastName=?, age=?, rating=? WHERE userID=?")
                        ->execute([$fname, $lname, $age, $rating, (int)$id]);
    }

    public function deleteUser($id) {
        return $this->db->prepare("DELETE FROM tbl_players WHERE userID=?")->execute([(int)$id]);
    }

    public function login($email, $password) {
        $stmt = $this->db->prepare("SELECT * FROM tbl_players WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) return 'not_found';
        if (!password_verify($password, $user['password'])) return 'wrong_password';
        $_SESSION['user']     = $user;
        $_SESSION['is_admin'] = false;
        return true;
    }

    public function adminLogin($email, $password) {
        $stmt = $this->db->prepare("SELECT * FROM tbl_admins WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) return 'not_found';
        if (!password_verify($password, $user['password'])) return 'wrong_password';
        $_SESSION['user'] = [
            'userID'    => $user['adminID'],
            'firstName' => $user['firstName'],
            'lastName'  => $user['lastName'],
            'email'     => $user['email'],
        ];
        $_SESSION['is_admin'] = true;
        return true;
    }

    public function adminExists() {
        return (int)$this->db->query("SELECT COUNT(*) FROM tbl_admins")->fetchColumn() > 0;
    }

    public function emailExists($email) {
        $s1 = $this->db->prepare("SELECT COUNT(*) FROM tbl_players WHERE email = ?");
        $s1->execute([$email]);
        if ($s1->fetchColumn() > 0) return true;

        $s2 = $this->db->prepare("SELECT COUNT(*) FROM tbl_admins WHERE email = ?");
        $s2->execute([$email]);
        return $s2->fetchColumn() > 0;
    }

    public function registerPlayer($fn, $ln, $gd, $ag, $rt, $email, $password) {
        if ($this->emailExists($email)) return 'email_taken';
        $sql = "INSERT INTO tbl_players (firstName, lastName, gender, age, rating, email, password) VALUES (?, ?, ?, ?, ?, ?, ?)";
        return $this->db->prepare($sql)->execute([$fn, $ln, $gd, $ag, $rt, $email, password_hash($password, PASSWORD_DEFAULT)]);
    }

    public function registerAdminFunc($fn, $ln, $email, $password) {
        if ($this->emailExists($email)) return 'email_taken';
        $check = $this->db->prepare("SELECT adminID FROM tbl_admins WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) return 'already_exists';
        $sql = "INSERT INTO tbl_admins (firstName, lastName, email, password) VALUES (?, ?, ?, ?)";
        return $this->db->prepare($sql)->execute([$fn, $ln, $email, password_hash($password, PASSWORD_DEFAULT)]);
    }

    public function updateUserFunc($id, $fn, $ln, $ag, $rt) {
        return $this->updateUser($id, $fn, $ln, $ag, $rt);
    }

    public function generatePairingFunc($round) {
        return $this->generatePairings($round);
    }

    public function resetTournamentFunc() {
        return $this->resetTournament();
    }

    public function getLogs() {
        try {
            return $this->db->query("SELECT log_id, user_id, action, created_at FROM tbl_logs ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public function addLog($user_id, $action) {
        try {
            $this->db->prepare("INSERT INTO tbl_logs (user_id, action, created_at) VALUES (?, ?, NOW())")->execute([(int)$user_id, $action]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
