<?php

namespace App\Livewire\Support;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class QuestionsBot extends Component
{
    public string $category = 'general';

    public ?string $selectedQuestion = null;

    public ?string $selectedAnswer = null;

    /** @var array<int, array{question: string, answer: string}> */
    public array $conversation = [];

    public function selectCategory(string $category): void
    {
        if (! array_key_exists($category, $this->questionBank())) {
            return;
        }

        $this->category = $category;
    }

    public function ask(string $category, string $question): void
    {
        $item = $this->questionBank()[$category]['items'][$question] ?? null;
        if (! is_array($item)) {
            return;
        }

        $this->category = $category;
        $this->selectedQuestion = $item['question'];
        $this->selectedAnswer = $item['answer'];

        if (count($this->conversation) >= 50) {
            array_shift($this->conversation);
        }

        $this->conversation[] = [
            'question' => $item['question'],
            'answer' => $item['answer'],
        ];
        $this->dispatch('support-answer-shown');
    }

    public function resetConversation(): void
    {
        $this->selectedQuestion = null;
        $this->selectedAnswer = null;
        $this->conversation = [];
    }

    public function render(): View
    {
        return view('livewire.support.questions-bot', [
            'questionBank' => $this->questionBank(),
        ])->layout('layouts.app');
    }

    /** @return array<string, array<string, mixed>> */
    private function questionBank(): array
    {
        return (array) config('support.questions', []);
    }
}
