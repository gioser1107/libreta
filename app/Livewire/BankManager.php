<?php

namespace App\Livewire;

use App\Actions\Banks\CreateBankAction;
use App\Actions\Banks\DeleteBankAction;
use App\Actions\Banks\RenameBankAction;
use App\Models\Bank;
use Livewire\Component;

class BankManager extends Component
{
    public string $name = '';

    public ?int $editingId = null;

    public ?int $confirmingRemovalId = null;

    public function mount(): void
    {
        abort_unless(auth()->check(), 403);
    }

    public function save(CreateBankAction $create, RenameBankAction $rename): void
    {
        $user = auth()->user();

        if ($this->editingId) {
            $rename->execute($user, $this->editingId, $this->name);
        } else {
            $create->execute($user, $this->name);
        }

        $this->resetForm();
    }

    public function edit(int $bankId): void
    {
        $bank = Bank::query()->ownedBy(auth()->user())->findOrFail($bankId);
        $this->editingId = $bank->id;
        $this->name = $bank->name;
        $this->confirmingRemovalId = null;
        $this->resetErrorBag();
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function askRemoval(int $bankId): void
    {
        Bank::query()->ownedBy(auth()->user())->findOrFail($bankId);
        $this->confirmingRemovalId = $bankId;
        $this->resetErrorBag();
    }

    public function cancelRemoval(): void
    {
        $this->confirmingRemovalId = null;
    }

    public function delete(int $bankId, DeleteBankAction $delete): void
    {
        $delete->execute(auth()->user(), $bankId);

        if ($this->editingId === $bankId) {
            $this->resetForm();

            return;
        }

        $this->confirmingRemovalId = null;
    }

    public function render()
    {
        return view('livewire.bank-manager', [
            'banks' => Bank::query()
                ->ownedBy(auth()->user())
                ->orderBy('name')
                ->orderBy('id')
                ->get(),
        ]);
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->confirmingRemovalId = null;
        $this->name = '';
        $this->resetErrorBag();
    }
}
