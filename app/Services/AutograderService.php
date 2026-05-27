<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\Submission;

class AutograderService
{
    /**
     * Grade a submission automatically based on assignment config.
     * Returns an array with ['score' => float, 'feedback' => string]
     */
    public function grade(Submission $submission): array
    {
        $assignment = $submission->assignment;
        $type = $assignment->type;
        $maxPoints = $assignment->max_points;
        $config = $assignment->config;

        if (empty($config)) {
            return [
                'score' => 0,
                'feedback' => 'No auto-grading configuration defined for this assignment.'
            ];
        }

        switch ($type) {
            case 'quiz':
                return $this->gradeQuiz($submission, $config, $maxPoints);
            case 'code':
                return $this->gradeCode($submission, $config, $maxPoints);
            case 'written':
                return $this->gradeWritten($submission, $config, $maxPoints);
            default:
                return [
                    'score' => 0,
                    'feedback' => 'Unknown assignment type for auto-grading.'
                ];
        }
    }

    /**
     * Grade a multiple choice quiz submission.
     */
    private function gradeQuiz(Submission $submission, array $config, float $maxPoints): array
    {
        // Student answers should be a JSON array of selected option indexes matching questions
        $answers = json_decode($submission->content, true);
        if (!is_array($answers)) {
            return [
                'score' => 0,
                'feedback' => 'Error: Invalid quiz submission format.'
            ];
        }

        $questions = $config['questions'] ?? [];
        $totalQuestions = count($questions);

        if ($totalQuestions === 0) {
            return [
                'score' => 0,
                'feedback' => 'Error: No questions configured for this quiz.'
            ];
        }

        $correctCount = 0;
        $feedbackParts = [];

        foreach ($questions as $index => $question) {
            $studentAnswer = $answers[$index] ?? null;
            $correctOption = $question['correct_option'] ?? null;
            $questionText = $question['question'] ?? ('Question ' . ($index + 1));
            $options = $question['options'] ?? [];

            $feedbackLine = "Question " . ($index + 1) . ": \"" . $questionText . "\"\n";

            if ($studentAnswer !== null && (int)$studentAnswer === (int)$correctOption) {
                $correctCount++;
                $selectedText = $options[$studentAnswer] ?? 'None';
                $feedbackLine .= "✅ Correct! You selected: \"" . $selectedText . "\"\n";
            } else {
                $selectedText = ($studentAnswer !== null && isset($options[$studentAnswer])) 
                    ? $options[$studentAnswer] 
                    : 'Unanswered';
                $correctText = isset($options[$correctOption]) 
                    ? $options[$correctOption] 
                    : 'Unknown';
                $feedbackLine .= "❌ Incorrect. You selected: \"" . $selectedText . "\". Correct option was: \"" . $correctText . "\"\n";
            }
            $feedbackParts[] = $feedbackLine;
        }

        $score = ($correctCount / $totalQuestions) * $maxPoints;
        $feedbackSummary = "Quiz Results: Passed " . $correctCount . " out of " . $totalQuestions . " questions.\n\n" 
            . implode("\n", $feedbackParts);

        return [
            'score' => round($score, 2),
            'feedback' => $feedbackSummary
        ];
    }

    /**
     * Grade a coding submission (PHP sandbox check / regex evaluation).
     */
    private function gradeCode(Submission $submission, array $config, float $maxPoints): array
    {
        $code = $submission->content;

        if (empty(trim($code))) {
            return [
                'score' => 0,
                'feedback' => "Error: No code submitted."
            ];
        }

        // Safety check to avoid dangerous execution
        $dangerousTokens = ['system', 'exec', 'shell_exec', 'passthru', 'popen', 'proc_open', 'eval', 'file_put_contents', 'unlink', 'rmdir', 'mkdir', '$_GET', '$_POST', '$_REQUEST', '$_COOKIE', '$_SESSION', 'env', 'config', 'DB::', 'Schema::', 'file_get_contents', 'curl_'];
        foreach ($dangerousTokens as $token) {
            if (stripos($code, $token) !== false) {
                return [
                    'score' => 0,
                    'feedback' => "🚨 Auto-grader Rejected: Unsafe code detected (contains token: '{$token}'). For security, submissions containing system level functions are locked."
                ];
            }
        }

        // Clean code (remove php open tags if present to execute safely)
        $cleanCode = preg_replace('/^<\?(php)?/i', '', $code);
        $cleanCode = preg_replace('/\?>$/', '', $cleanCode);

        $testCases = $config['test_cases'] ?? [];
        $requiredFunction = $config['required_function'] ?? '';
        $totalTestCases = count($testCases);

        $feedbackParts = [];
        $passedCases = 0;

        // Verify if required function is defined
        if (!empty($requiredFunction)) {
            // Check via regex if function exists
            $pattern = '/function\s+' . preg_quote($requiredFunction, '/') . '\s*\(/i';
            if (!preg_match($pattern, $cleanCode)) {
                return [
                    'score' => 0,
                    'feedback' => "❌ Compilation Error: The required function `{$requiredFunction}` was not defined in your code.\n\nMake sure your function is named exactly: `function {$requiredFunction}(...)`"
                ];
            }
            $feedbackParts[] = "✅ Structure Check: Function `{$requiredFunction}` is declared.";
        }

        // Run test cases
        if ($totalTestCases > 0) {
            // First evaluate the student's code once to declare the function in the current process
            try {
                ob_start();
                eval($cleanCode);
                ob_end_clean();
            } catch (\Throwable $e) {
                ob_end_clean();
                return [
                    'score' => 0,
                    'feedback' => "❌ Compilation Error: The code has syntax errors.\n\nDetails: " . $e->getMessage()
                ];
            }

            foreach ($testCases as $index => $tc) {
                $input = $tc['input'] ?? '';
                $expected = trim($tc['expected_output'] ?? '');

                // We evaluate the function call
                $evalString = "return {$requiredFunction}({$input});";

                try {
                    // Safe execution using eval for basic mathematical/string operations
                    // Output buffering captures any direct echos
                    ob_start();
                    $result = eval($evalString);
                    $bufferedOutput = ob_get_clean();

                    // If they printed output rather than returning, check that too
                    $actual = trim(!empty($bufferedOutput) ? $bufferedOutput : (string)$result);

                    if ($actual === $expected) {
                        $passedCases++;
                        $feedbackParts[] = "Case " . ($index + 1) . ": Input `{$input}` -> Expected `{$expected}`. ✅ Passed!";
                    } else {
                        $feedbackParts[] = "Case " . ($index + 1) . ": Input `{$input}` -> Expected `{$expected}`, but got `{$actual}`. ❌ Failed.";
                    }
                } catch (\Throwable $e) {
                    ob_end_clean();
                    $feedbackParts[] = "Case " . ($index + 1) . ": Input `{$input}`. 💥 Runtime Error: " . $e->getMessage();
                }
            }

            $score = ($passedCases / $totalTestCases) * $maxPoints;
            $feedbackSummary = "Coding Results: Passed " . $passedCases . " out of " . $totalTestCases . " test cases.\n\n"
                . implode("\n", $feedbackParts);
        } else {
            // If no test cases are specified, perform a basic PHP syntax lint
            try {
                ob_start();
                $result = eval($cleanCode . "\n return true;");
                ob_get_clean();
                $score = $maxPoints;
                $feedbackSummary = "✅ Syntax Check Passed! The code compiles successfully.\n\nNo test cases were configured for this assignment, so full points were awarded.";
            } catch (\Throwable $e) {
                ob_end_clean();
                $score = 0;
                $feedbackSummary = "❌ Syntax Error: The code failed to run.\nError details: " . $e->getMessage();
            }
        }

        return [
            'score' => round($score, 2),
            'feedback' => $feedbackSummary
        ];
    }

    /**
     * Grade a written/rubric assignment based on keyword coverage.
     */
    private function gradeWritten(Submission $submission, array $config, float $maxPoints): array
    {
        $text = strtolower($submission->content ?? '');
        $rubrics = $config['rubrics'] ?? [];

        if (count($rubrics) === 0) {
            return [
                'score' => $maxPoints,
                'feedback' => "✅ Submission received. No auto-grading rubric was specified, so full score is provisionally awarded. The teacher will review your submission shortly."
            ];
        }

        $earnedScore = 0;
        $totalMaxRubricPoints = 0;
        $feedbackParts = [];

        foreach ($rubrics as $index => $rubric) {
            $name = $rubric['name'] ?? ('Criteria ' . ($index + 1));
            $rubricPoints = (float)($rubric['points'] ?? 0);
            $totalMaxRubricPoints += $rubricPoints;

            $keywords = $rubric['keywords'] ?? [];
            $matchedKeywords = [];

            foreach ($keywords as $keyword) {
                $cleanedKeyword = trim(strtolower($keyword));
                if (!empty($cleanedKeyword) && strpos($text, $cleanedKeyword) !== false) {
                    $matchedKeywords[] = $keyword;
                }
            }

            $keywordCount = count($keywords);
            $matchedCount = count($matchedKeywords);

            if ($keywordCount === 0) {
                // If rubric has no keywords, auto-grader awards full points for the section
                $rubricEarned = $rubricPoints;
                $feedbackParts[] = "📌 {$name} (+{$rubricEarned}/{$rubricPoints} pts)\n  - Checked by teacher (No automated keywords defined).";
            } else {
                // Award points proportionally based on keyword presence
                $fraction = $matchedCount / $keywordCount;
                $rubricEarned = $fraction * $rubricPoints;
                $keywordList = implode(', ', $keywords);
                $matchedList = implode(', ', $matchedKeywords);

                $feedbackParts[] = "📌 {$name} (+ " . round($rubricEarned, 2) . "/{$rubricPoints} pts)\n"
                    . "  - Expected terms: [{$keywordList}]\n"
                    . "  - Matched terms: [{$matchedList}] ({$matchedCount}/{$keywordCount} matched)";
            }

            $earnedScore += $rubricEarned;
        }

        // Scale the score to the assignment max_points if rubric total points differ
        if ($totalMaxRubricPoints > 0) {
            $scaledScore = ($earnedScore / $totalMaxRubricPoints) * $maxPoints;
        } else {
            $scaledScore = $maxPoints;
        }

        $feedbackSummary = "📝 Automated Rubric Analysis:\n"
            . "Tentative Score: " . round($scaledScore, 2) . " / " . $maxPoints . " pts\n"
            . "(Based on keywords analysis. Teacher may override this score)\n\n"
            . implode("\n\n", $feedbackParts);

        return [
            'score' => round($scaledScore, 2),
            'feedback' => $feedbackSummary
        ];
    }
}
