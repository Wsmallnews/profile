<?php

namespace Wsmallnews\Profile\Livewire\Components\Address;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;
use Wsmallnews\Profile\Filament\Address\Schemas\AddressForm;
use Wsmallnews\Profile\Livewire\Components\Base;
use Wsmallnews\Profile\Models\Address;
use Wsmallnews\Support\Livewire\Concerns\CanBeContained;

/**
 * 个人中心地址簿管理（增删改查 + 设默认）
 *
 * <livewire:sn-profile::components.address.addresses :owner="$member" :contained="false" />
 *
 * owner / contained 等公共属性由 Livewire 按 blade 传参自动注入，无需 mount。
 * ChooseAddress 继承本组件复用 CRUD 骨架（hook：addressCreateLabel / addressCreated）。
 */
class Addresses extends Base implements HasActions, HasForms
{
    use CanBeContained;
    use InteractsWithActions;
    use InteractsWithForms;

    #[Locked]
    public Model $owner;

    /**
     * @return array<string, mixed>
     */
    public function getViewData(): array
    {
        return [
            'addresses' => $this->owner->addresses()->get(),
        ];
    }

    public function createAction(): CreateAction
    {
        return CreateAction::make()
            ->label($this->addressCreateLabel())
            ->modalHeading(__('sn-profile::profile.address.create'))
            ->schema(AddressForm::schema())
            ->createAnother(false)
            ->using(function (array $data): Address {
                /** @var Address $address */
                $address = $this->owner->addresses()->create(array_merge(
                    AddressForm::mutate($data),
                    ['team_id' => $this->ownerTeamId()],
                ));

                // 首个地址自动设为默认
                if ($address->is_default || ! $this->owner->addresses()->whereKeyNot($address->id)->exists()) {
                    $address->setDefault();
                }

                $this->addressCreated($address);

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
            ->successNotificationTitle(__('sn-profile::profile.address.edit_success'))
            ->link();
    }

    public function setDefaultAction(): Action
    {
        return Action::make('setDefault')
            ->label(__('sn-profile::profile.address.set_default'))
            ->record(fn (array $arguments) => $this->findAddress($arguments))
            ->action(function (Address $record): void {
                $record->setDefault();
            })
            ->link();
    }

    public function deleteAction(): DeleteAction
    {
        return DeleteAction::make('delete')
            ->label(__('sn-profile::profile.address.delete'))
            ->record(fn (array $arguments) => $this->findAddress($arguments))
            ->successNotificationTitle(__('sn-profile::profile.address.delete_success'))
            ->link();
    }

    public function render()
    {
        return view('sn-profile::livewire.components.address.addresses', $this->getViewData());
    }

    /**
     * 新建入口文案（ChooseAddress 覆盖为“使用新地址”）
     */
    protected function addressCreateLabel(): string
    {
        return __('sn-profile::profile.address.create');
    }

    /**
     * 地址创建后的钩子（ChooseAddress 覆盖为选中并派发事件）
     */
    protected function addressCreated(Address $address): void {}

    /**
     * owner 范围内解析地址（越权直接 404）
     */
    protected function findAddress(array $arguments): ?Address
    {
        $id = (int) ($arguments['id'] ?? 0);

        /** @var Address $address */
        $address = $this->owner->addresses()->whereKey($id)->firstOrFail();

        return $address;
    }

    /**
     * owner 为 Member 时冗余租户隔离列；User 等全局 owner 为 null
     */
    protected function ownerTeamId(): ?int
    {
        $teamId = $this->owner->team_id ?? null;

        return $teamId === null ? null : (int) $teamId;
    }
}
