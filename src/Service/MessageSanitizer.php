<?php

namespace App\Service;

class MessageSanitizer
{
    public function sanitize(string $content): string
    {
        // Supprimer les balises HTML dangereuses
        $content = strip_tags($content, '<p><br><strong><em><u>');
        
        // Échapper les caractères spéciaux
        $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
        
        // Supprimer les espaces multiples
        $content = preg_replace('/\s+/', ' ', $content);
        
        // Supprimer les espaces en début et fin
        $content = trim($content);
        
        return $content;
    }

    public function validateLength(string $content, int $maxLength = 2000): bool
    {
        return strlen($content) <= $maxLength;
    }

    public function validateContent(string $content): array
    {
        $errors = [];
        
        if (empty(trim($content))) {
            $errors[] = 'Le contenu du message ne peut pas être vide';
        }
        
        if (!$this->validateLength($content)) {
            $errors[] = 'Le message ne peut pas dépasser 2000 caractères';
        }
        
        // Vérifier les mots interdits (optionnel)
        $forbiddenWords = ['spam', 'scam', 'phishing'];
        foreach ($forbiddenWords as $word) {
            if (stripos($content, $word) !== false) {
                $errors[] = 'Le message contient des mots interdits';
                break;
            }
        }
        
        return $errors;
    }
}
