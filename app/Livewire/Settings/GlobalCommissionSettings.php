<?php

namespace App\Livewire\Settings;

use App\Models\GlobalCommission;
use Livewire\Component;
use Livewire\WithPagination;

class GlobalCommissionSettings extends Component
{
    use WithPagination;

    public $showModal = false;
    public $editMode = false;
    public $commissionId = null;
    
    public $name = '';
    public $commission_rate = '';
    public $description = '';
    public $is_active = true;

    protected $rules = [
        'name' => 'required|string|max:255',
        'commission_rate' => 'required|numeric|min:0|max:100',
        'description' => 'nullable|string|max:500',
        'is_active' => 'boolean',
    ];

    public function render()
    {
        $commissions = GlobalCommission::orderBy('is_active', 'desc')
                                    ->orderBy('created_at', 'desc')
                                    ->paginate(10);

        return view('livewire.settings.global-commission-settings', [
            'commissions' => $commissions,
            'activeCommission' => GlobalCommission::getActiveCommission(),
        ]);
    }

    public function openModal()
    {
        $this->resetForm();
        $this->editMode = false;
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->name = '';
        $this->commission_rate = '';
        $this->description = '';
        $this->is_active = true;
        $this->commissionId = null;
        $this->resetValidation();
    }

    public function save()
    {
        $this->validate();

        if ($this->editMode) {
            $commission = GlobalCommission::findOrFail($this->commissionId);
            $commission->update([
                'name' => $this->name,
                'commission_rate' => $this->commission_rate,
                'description' => $this->description,
                'is_active' => $this->is_active,
            ]);

            if ($this->is_active) {
                $commission->activate();
            }

            session()->flash('message', 'Commission updated successfully!');
        } else {
            $commission = GlobalCommission::create([
                'name' => $this->name,
                'commission_rate' => $this->commission_rate,
                'description' => $this->description,
                'is_active' => $this->is_active,
            ]);

            if ($this->is_active) {
                $commission->activate();
            }

            session()->flash('message', 'Commission created successfully!');
        }

        $this->closeModal();
    }

    public function edit($id)
    {
        $commission = GlobalCommission::findOrFail($id);
        
        $this->commissionId = $commission->id;
        $this->name = $commission->name;
        $this->commission_rate = $commission->commission_rate;
        $this->description = $commission->description;
        $this->is_active = $commission->is_active;
        
        $this->editMode = true;
        $this->showModal = true;
    }

    public function activate($id)
    {
        $commission = GlobalCommission::findOrFail($id);
        $commission->activate();
        
        session()->flash('message', 'Commission activated successfully!');
    }

    public function delete($id)
    {
        $commission = GlobalCommission::findOrFail($id);
        
        // Don't allow deleting the active commission if it's the only one
        if ($commission->is_active && GlobalCommission::count() === 1) {
            session()->flash('error', 'Cannot delete the only commission record!');
            return;
        }

        $commission->delete();
        
        session()->flash('message', 'Commission deleted successfully!');
    }
}
