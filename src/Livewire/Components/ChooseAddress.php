<?php

namespace Wsmallnews\Profile\Livewire\Components;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Wsmallnews\Profile\Filament\Schemas\AddressForm;
use Wsmallnews\Profile\Models\Address;
use Wsmallnews\Support\Concerns\HasColumns;
use Wsmallnews\Support\Livewire\Concerns\CanBeContained;

/**
 * 下单收货地址选择（默认地址自动选中 + 新增/编辑 + 事件通知宿主）
 *
 * <livewire:sn-profile::components.choose-address :owner="$member" :manage-url="..." />
 *
 * 选择后 dispatch：sn-profile-address:selected（addressId）
 */
class ChooseAddress extends Base implements HasActions, HasForms
{
    use CanBeContained;
    use HasColumns;
    use InteractsWithActions;
    use InteractsWithForms;

    #[Locked]
    public Model $owner;

    /**
     * “管理收货地址”页面链接（调用方注入，如个人中心地址页）
     */
    public ?string $manageUrl = null;

    #[Locked]
    public ?int $selectedId = null;

    public function mount(Model $owner, ?string $manageUrl = null, ?int $selectedId = null, ?int $columns = null, bool $contained = true): void
    {
        $this->owner = $owner;
        $this->manageUrl = $manageUrl;
        $this->contained = $contained;
        $this->columns($columns);

        // 未显式指定时自动选中默认地址（默认优先 + 新建优先）
        $this->selectedId = $selectedId ?? $this->owner->addresses()->value('id');
    }

    public function choose(int $id): void
    {
        $address = $this->owner->addresses()->whereKey($id)->first();

        if (! $address) {
            return;
        }

        $this->selectedId = $address->id;

        $this->dispatch('sn-profile-address:selected', addressId: $address->id);
    }

    public function createAction(): CreateAction
    {
        return CreateAction::make()
            ->label(__('sn-profile::profile.address.use_new_address'))
            ->modalHeading(__('sn-profile::profile.address.create'))
            ->schema(AddressForm::schema())
            ->createAnother(false)
            ->using(function (array $data): Address {
                /** @var Address $address */
                $address = $this->owner->addresses()->create(array_merge(
                    AddressForm::mutate($data),
                    ['team_id' => $this->owner->team_id ?? null],
                ));

                if ($address->is_default || ! $this->owner->addresses()->whereKeyNot($address->id)->exists()) {
                    $address->setDefault();
                }

                // 新增后直接选中并通知宿主
                $this->selectedId = $address->id;
                $this->dispatch('sn-profile-address:selected', addressId: $address->id);

                return $address;
            })
            ->successNotificationTitle(__('sn-profile::profile.address.create_success'));
    }

    public function editAction(): EditAction
    {
        return EditAction::make('edit')
            ->label(__('sn-profile::profile.address.edit'))
            ->modalHeading(__('sn-profile::profile.address.edit'))
            ->record(fn (array $arguments) => $this->findAddress($arguments))
            ->schema(AddressForm::schema())
            ->using(function (Address $record, array $data): Address {
                $record->update(AddressForm::mutate($data));

                if ($record->wasChanged('is_default') && $record->is_default) {
                    $record->setDefault();
                }

                return $record;
            })
            ->successNotificationTitle(__('sn-profile::profile.address.edit_success'));
    }

    public function render()
    {
        return view('sn-profile::livewire.components.choose-address');
    }

    /**
     * @return array<string, mixed>
     */
    public function getViewData(): array
    {
        return [
            'addresses' => $this->owner->addresses()->get(),
        ];
    }

    protected function findAddress(array $arguments): ?Address
    {
        $id = (int) ($arguments['id'] ?? 0);

        /** @var Address $address */
        $address = $this->owner->addresses()->whereKey($id)->firstOrFail();

        return $address;
    }
}
