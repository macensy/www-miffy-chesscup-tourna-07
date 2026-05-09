<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../model/database.php";

class usermanager {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connectDB();
    }

    private function addLog($action) {
        $uID = $_SESSION['user']['userID'] ?? '0';
        $sql = "INSERT INTO tbl_logs (user_id, action, created_at) VALUES (?, ?, NOW())";
        $this->db->prepare($sql)->execute([$uID, $action]);
    }

    public function login($fn, $ln) {
        $sql = "SELECT * FROM tbl_players WHERE firstName = ? AND lastName = ?";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$fn, $ln]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $_SESSION['user'] = $user; 
                $this->addLog("User Logged In: " . $user['firstName'] . " " . $user['lastName']);
                return true;
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    public function registerAdminFunc($id, $fn, $ln, $role) {
        try {
            $sql = "INSERT INTO tbl_players (userID, firstName, lastName, role, gender, age, rating) 
                    VALUES (?, ?, ?, ?, 'N/A', 0, 0)";
            
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute([$id, $fn, $ln, $role]);

            if ($success) {
                $this->addLog("Registered Admin: $fn $ln (ID: $id)");
                return true;
            }
            return "Failed to insert into players table.";
        } catch (Exception $e) {
            return "SQL Error: " . $e->getMessage(); 
        }
    } 

    public function registerPlayer($fn, $ln, $gd, $ag, $rt, $role) {
        $sql = "INSERT INTO tbl_players (firstName, lastName, gender, age, rating, role) 
                VALUES (?, ?, ?, ?, ?, ?)";
        try {
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute([$fn, $ln, $gd, $ag, $rt, $role]);

            if ($success) {
                $this->addLog("Registered Player: $fn $ln");
            }
            return $success;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getUser() {
        $sql = "SELECT * FROM tbl_players WHERE role != 'Admin' ORDER BY userID ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLeaderboards() {
        $sql = "SELECT p.*, 
                (SELECT IFNULL(SUM(p1_score), 0) FROM tbl_pairing WHERE player1_id = p.userID) + 
                (SELECT IFNULL(SUM(p2_score), 0) FROM tbl_pairing WHERE player2_id = p.userID) as total_pts 
                FROM tbl_players p 
                WHERE role = 'Player' 
                ORDER BY total_pts DESC, rating DESC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPairings($round = 1) {
        try {
            $sql = "SELECT m.*, p1.firstName AS p1Name, p2.firstName AS p2Name 
                    FROM tbl_pairing m
                    INNER JOIN tbl_players p1 ON m.player1_id = p1.userID
                    INNER JOIN tbl_players p2 ON m.player2_id = p2.userID
                    WHERE m.round_num = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$round]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) { return []; }
    }

    public function getLogs() {
        $sql = "SELECT l.*, p.firstName, p.lastName FROM tbl_logs l 
                LEFT JOIN tbl_players p ON l.user_id = p.userID ORDER BY l.created_at DESC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateUserFunc($id, $fn, $ln, $ag, $rt) {
        try {
            $sql = "UPDATE tbl_players SET firstName = :fn, lastName = :ln, age = :ag, rating = :rt WHERE userID = :id";
            $stmt = $this->db->prepare($sql);
            
            $stmt->bindParam(':fn', $fn);
            $stmt->bindParam(':ln', $ln);
            $stmt->bindParam(':ag', $ag);
            $stmt->bindParam(':rt', $rt);
            $stmt->bindParam(':id', $id);
            
            $success = $stmt->execute();

            if ($success) {
                $logAction = "Updated Player: " . strtoupper($fn) . " " . strtoupper($ln) . " (ID: $id)";
                $this->addLog($logAction);
            }
            return $success;
        } catch (Exception $e) {
            return false;
        }
    }


    
    public function deleteUser($id) {
        $sql = "DELETE FROM tbl_players WHERE userID = ?"; 
        $stmt = $this->db->prepare($sql); 
        return $stmt->execute([$id]);
    }

    public function generatePairingFunc($round = 1) {
        try {
            if ($round > 1) {
                $prev = $round - 1;
                $check = $this->db->prepare("SELECT COUNT(*) FROM tbl_pairing WHERE round_num = ? AND status != 'FINISHED'");
                $check->execute([$prev]);
                if ($check->fetchColumn() > 0) return "Round $prev is still ongoing!";
            }

            $this->db->prepare("DELETE FROM tbl_pairing WHERE round_num = ?")->execute([$round]);
            $players = $this->db->query("SELECT userID FROM tbl_players WHERE role = 'Player'")->fetchAll(PDO::FETCH_COLUMN);

            if (count($players) < 2) return "Need more players.";
            shuffle($players);

            $stmt = $this->db->prepare("INSERT INTO tbl_pairing (player1_id, player2_id, p1_score, p2_score, round_num, status) VALUES (?, ?, 0, 0, ?, 'PENDING')");
            for ($i = 0; $i < count($players); $i += 2) {
                if (isset($players[$i+1])) $stmt->execute([$players[$i], $players[$i+1], $round]);
            }
            return true;
        } catch (Exception $e) { return $e->getMessage(); }
    }

    public function updateStandings() {
    // 1. Linisin ang table
    $this->db->query("TRUNCATE tbl_standings");

    $sql = "INSERT INTO tbl_standings (player_id, player_name, total_score)
            SELECT 
                p.userID, 
                CONCAT(p.firstName, ' ', p.lastName),
                SUM(
                    CASE 
                        WHEN pair.status = 'FINISHED' AND pair.winner_id = p.userID THEN 1.0
                        WHEN pair.status = 'FINISHED' AND pair.p1_score = pair.p2_score AND (pair.player1_id = p.userID OR pair.player2_id = p.userID) THEN 0.5
                        ELSE 0.0 
                    END
                ) as points
            FROM tbl_players p
            LEFT JOIN tbl_pairing pair ON (p.userID = pair.player1_id OR p.userID = pair.player2_id)
            WHERE p.role = 'Player'
            GROUP BY p.userID
            ORDER BY points DESC";
            
    return $this->db->query($sql);
}
    public function resetTournamentFunc() {
        try {
            $this->db->query("TRUNCATE TABLE tbl_pairing");
            $this->db->query("UPDATE tbl_standings SET total_score = 0");
            return true;
        } catch (PDOException $e) {
            return $e->getMessage();
        }
    }


  public function getAdminDashboardStats($currentRound = 1) {
    $stats = [];

    $stats['registered_players'] = $this->db->query("SELECT COUNT(*) FROM tbl_players WHERE role = 'Player'")->fetchColumn();

    $sqlUnfinished = "SELECT COUNT(*) FROM tbl_pairing WHERE round_num = ? AND status != 'FINISHED'";
    $stmt = $this->db->prepare($sqlUnfinished);
    $stmt->execute([(int)$currentRound]);
    $stats['unfinished_matches'] = $stmt->fetchColumn();

    $sqlCompletedRounds = "SELECT COUNT(DISTINCT round_num) FROM tbl_pairing 
                           WHERE round_num NOT IN (SELECT DISTINCT round_num FROM tbl_pairing WHERE status != 'FINISHED')";
    $stats['completed_rounds'] = $this->db->query($sqlCompletedRounds)->fetchColumn();

    $stats['male_count']   = (int)$this->db->query("SELECT COUNT(*) FROM tbl_players WHERE role = 'Player' AND gender = 'Male'")->fetchColumn();
    $stats['female_count'] = (int)$this->db->query("SELECT COUNT(*) FROM tbl_players WHERE role = 'Player' AND gender = 'Female'")->fetchColumn();

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
    else if ($s2 > $s1) $winner = $p2;

    $sql = "UPDATE tbl_pairing SET p1_score = ?, p2_score = ?, winner_id = ?, status = 'FINISHED' WHERE match_id = ?";
    $res = $this->db->prepare($sql)->execute([$s1, $s2, $winner, (int)$mid]);

    if($res) {
        $this->updateStandings();
        return "true";
    }
    return "false";
}
}
?>