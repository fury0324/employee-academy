<?php
// includes/NotificationHelper.php

class NotificationHelper {
    private $conn;
    
    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }
    
    /**
     * Send notification to a specific user
     */
    public function send($user_id, $type, $title, $message, $link = null) {
        $query = "INSERT INTO notifications (user_id, type, title, message, link, created_at) 
                  VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("issss", $user_id, $type, $title, $message, $link);
        return $stmt->execute();
    }
    
    /**
     * Send notification to all admins
     */
    public function sendToAllAdmins($type, $title, $message, $link = null) {
        $query = "SELECT id FROM users WHERE role IN ('admin', 'super_admin')";
        $result = $this->conn->query($query);
        
        $success = true;
        while ($admin = $result->fetch_assoc()) {
            if (!$this->send($admin['id'], $type, $title, $message, $link)) {
                $success = false;
            }
        }
        return $success;
    }
    
    /**
     * Notify when new employee registers
     */
    public function notifyNewRegistration($employee_id, $employee_name, $employee_email) {
        $title = "New Employee Registration";
        $message = "$employee_name ($employee_email) has registered and is waiting for approval.";
        $link = "admin_approval.php";
        
        return $this->sendToAllAdmins('user_registration', $title, $message, $link);
    }
    
    /**
     * Notify when employee completes a course
     */
    public function notifyCourseCompleted($employee_name, $course_name, $progress_percentage = 100) {
        $title = "Course Completed! 🎉";
        $message = "$employee_name has successfully completed the course: $course_name";
        $link = "reports.php?type=completed_courses";
        
        return $this->sendToAllAdmins('course_completed', $title, $message, $link);
    }
    
    /**
     * Notify about course status change
     */
    public function notifyCourseStatusChange($employee_name, $course_name, $status, $progress = null) {
        $status_labels = [
            'not_started' => 'Not Started',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'failed' => 'Failed',
            'on_hold' => 'On Hold'
        ];
        
        $status_text = $status_labels[$status] ?? ucfirst($status);
        $title = "Course Status Update";
        $message = "$employee_name's course \"$course_name\" is now: $status_text";
        
        if ($progress !== null) {
            $message .= " (Progress: $progress%)";
        }
        
        $link = "course_management.php?course=" . urlencode($course_name);
        
        return $this->sendToAllAdmins('course_status', $title, $message, $link);
    }
    
    /**
     * Notify about retake request
     */
    public function notifyRetakeRequest($employee_name, $course_name, $reason) {
        $title = "⚠️ Course Retake Request";
        $message = "$employee_name is requesting to retake the course: $course_name";
        
        if ($reason) {
            $message .= " Reason: $reason";
        }
        
        $link = "retake_requests.php";
        
        return $this->sendToAllAdmins('retake_request', $title, $message, $link);
    }
    
    /**
     * Notify about quiz submission
     */
    public function notifyQuizSubmitted($employee_name, $quiz_name, $score, $total_items) {
        $percentage = round(($score / $total_items) * 100);
        $title = "Quiz Submitted";
        $message = "$employee_name submitted quiz: $quiz_name - Score: $score/$total_items ($percentage%)";
        $link = "quiz_management.php?view=submissions";
        
        return $this->sendToAllAdmins('quiz_submitted', $title, $message, $link);
    }
    
    /**
     * Notify about low quiz score (below passing)
     */
    public function notifyLowQuizScore($employee_name, $quiz_name, $score, $total_items, $passing_score) {
        $title = "⚠️ Low Quiz Score Alert";
        $message = "$employee_name scored $score/$total_items on $quiz_name (Below passing score of $passing_score)";
        $link = "quiz_management.php?view=failed_attempts";
        
        return $this->sendToAllAdmins('warning', $title, $message, $link);
    }
    
    /**
     * Notify about employee progress milestone
     */
    public function notifyProgressMilestone($employee_name, $milestone_percentage) {
        $title = "Progress Milestone Reached! 🎯";
        $message = "$employee_name has reached $milestone_percentage% completion of their training program";
        $link = "reports.php?type=progress_report";
        
        return $this->sendToAllAdmins('milestone', $title, $message, $link);
    }
}
?>