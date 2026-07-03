<?php

namespace App\Support;

use Illuminate\Support\Str;

final class AssetCategoryProfile
{
    public static function key(?string $category): string
    {
        $normalized = Str::of($category ?? '')
            ->lower()
            ->replace(['_', '-'], ' ')
            ->squish()
            ->toString();

        if ($normalized === '') {
            return 'other';
        }

        if (Str::contains($normalized, ['software', 'license'])) {
            return 'software';
        }

        if (Str::contains($normalized, 'laptop')) {
            return 'laptop';
        }

        if (in_array($normalized, ['pc', 'pc / laptop', 'pc laptop'], true)) {
            return 'pc';
        }

        if (Str::contains($normalized, 'monitor')) {
            return 'monitor';
        }

        if (Str::contains($normalized, ['printer', 'scanner'])) {
            return 'printer';
        }

        if (Str::contains($normalized, ['network', 'router', 'switch', 'access point'])) {
            return 'network';
        }

        if (Str::contains($normalized, ['cctv', 'nvr', 'dvr'])) {
            return 'cctv';
        }

        if (Str::contains($normalized, ['peripheral', 'keyboard', 'mouse', 'ups', 'projector'])) {
            return 'peripheral';
        }

        return 'other';
    }

    public static function formProfiles(): array
    {
        $computer = [
            'description' => 'Record computer identity, hardware specifications, and network details.',
            'sub_label' => 'Device Type',
            'sub_placeholder' => 'e.g. Desktop, Mini PC, Workstation',
            'detail_heading' => 'Computer Details',
            'brand_placeholder' => 'e.g. Lenovo, Dell, HP',
            'model_placeholder' => 'e.g. ThinkCentre M80, OptiPlex 7090',
            'serial_label' => 'Serial Number',
            'serial_placeholder' => 'e.g. S/N or Service Tag',
            'location_placeholder' => 'e.g. Finance Room, IT Work Area',
            'notes_label' => 'Technical / Maintenance Notes',
            'notes_placeholder' => 'e.g. Upgrade history, hardware issues, or maintenance notes...',
            'specs_label' => 'Specifications',
            'specs_placeholder' => '',
            'show_ip' => true,
            'show_specs' => false,
            'show_computer' => true,
        ];

        return [
            'pc' => array_merge($computer, [
                'create_title' => 'Add PC',
                'edit_title' => 'Edit PC',
                'name_placeholder' => 'e.g. Finance Workstation 01',
            ]),
            'laptop' => array_merge($computer, [
                'create_title' => 'Add Laptop',
                'edit_title' => 'Edit Laptop',
                'name_placeholder' => 'e.g. Sales Laptop 01',
                'sub_placeholder' => 'e.g. Business Laptop, Ultrabook',
            ]),
            'monitor' => [
                'create_title' => 'Add Monitor',
                'edit_title' => 'Edit Monitor',
                'description' => 'Record monitor identity, display size, and connection details.',
                'name_placeholder' => 'e.g. Dell P2419H Finance',
                'sub_label' => 'Display Type',
                'sub_placeholder' => 'e.g. LED, IPS, Ultrawide',
                'detail_heading' => 'Display Details',
                'brand_placeholder' => 'e.g. Dell, Samsung, LG',
                'model_placeholder' => 'e.g. P2419H, S24F350',
                'serial_label' => 'Serial Number',
                'serial_placeholder' => 'e.g. Monitor serial number',
                'location_placeholder' => 'e.g. Finance Desk 01',
                'notes_label' => 'Display / Condition Notes',
                'notes_placeholder' => 'e.g. Dead pixels, stand condition, or assigned desk...',
                'specs_label' => 'Display Specifications',
                'specs_placeholder' => 'Connection: HDMI | Size: 52 x 29 cm | Resolution: 1920x1080',
                'show_ip' => false,
                'show_specs' => true,
                'show_computer' => false,
            ],
            'printer' => [
                'create_title' => 'Add Printer / Scanner',
                'edit_title' => 'Edit Printer / Scanner',
                'description' => 'Record printer identity, network information, and operational details.',
                'name_placeholder' => 'e.g. Printer Finance, Scanner Warehouse',
                'sub_label' => 'Device Type',
                'sub_placeholder' => 'e.g. Printer, Scanner, Multifunction',
                'detail_heading' => 'Printer Details',
                'brand_placeholder' => 'e.g. Epson, HP, Canon, Brother',
                'model_placeholder' => 'e.g. L3110, M404dn, G4010',
                'serial_label' => 'Serial Number',
                'serial_placeholder' => 'e.g. Printer serial number',
                'location_placeholder' => 'e.g. Finance Room, Print Area',
                'notes_label' => 'Maintenance / Supply Notes',
                'notes_placeholder' => 'e.g. Toner condition, maintenance history, or known print issues...',
                'specs_label' => 'Printer Specifications',
                'specs_placeholder' => 'Connection: LAN | Print Type: Color | Paper: A4',
                'show_ip' => true,
                'show_specs' => true,
                'show_computer' => false,
            ],
            'network' => [
                'create_title' => 'Add Network Device',
                'edit_title' => 'Edit Network Device',
                'description' => 'Record network identity, management address, and interface capacity.',
                'name_placeholder' => 'e.g. Core Switch F3, AP Warehouse 01',
                'sub_label' => 'Device Type',
                'sub_placeholder' => 'e.g. Router, Switch, Access Point',
                'detail_heading' => 'Network Details',
                'brand_placeholder' => 'e.g. Cisco, MikroTik, Ubiquiti',
                'model_placeholder' => 'e.g. SG350-28, CCR2004, U6 Pro',
                'serial_label' => 'Serial Number',
                'serial_placeholder' => 'e.g. Device serial number',
                'location_placeholder' => 'e.g. Server Room, Rack A3',
                'notes_label' => 'Configuration / Maintenance Notes',
                'notes_placeholder' => 'e.g. VLAN role, rack position, backup, or maintenance notes...',
                'specs_label' => 'Network Specifications',
                'specs_placeholder' => 'Ports: 24 | Speed: 1 Gbps | Firmware: 1.0.0 | MAC: 00:11:22:33:44:55',
                'show_ip' => true,
                'show_specs' => true,
                'show_computer' => false,
            ],
            'cctv' => [
                'create_title' => 'Add CCTV / Recorder',
                'edit_title' => 'Edit CCTV / Recorder',
                'description' => 'Record camera or recorder identity, network address, and video specifications.',
                'name_placeholder' => 'e.g. Camera Gate 01, NVR Security Room',
                'sub_label' => 'Device Type',
                'sub_placeholder' => 'e.g. IP Camera, Analog Camera, NVR, DVR',
                'detail_heading' => 'Surveillance Details',
                'brand_placeholder' => 'e.g. Hikvision, Dahua, Uniview',
                'model_placeholder' => 'e.g. DS-2CD1023G0, NVR4108HS',
                'serial_label' => 'Serial Number',
                'serial_placeholder' => 'e.g. Camera or recorder serial',
                'location_placeholder' => 'e.g. Main Gate, Security Room',
                'notes_label' => 'Coverage / Maintenance Notes',
                'notes_placeholder' => 'e.g. Camera direction, blind spots, recording issues, or maintenance...',
                'specs_label' => 'Surveillance Specifications',
                'specs_placeholder' => 'Resolution: 4 MP | Channels: 8 | Storage: 2 TB | Connection: PoE',
                'show_ip' => true,
                'show_specs' => true,
                'show_computer' => false,
            ],
            'peripheral' => [
                'create_title' => 'Add Peripheral',
                'edit_title' => 'Edit Peripheral',
                'description' => 'Record accessory identity, interface, compatibility, and condition.',
                'name_placeholder' => 'e.g. Logitech Keyboard Finance, UPS Server 01',
                'sub_label' => 'Peripheral Type',
                'sub_placeholder' => 'e.g. Keyboard, Mouse, UPS, Projector',
                'detail_heading' => 'Peripheral Details',
                'brand_placeholder' => 'e.g. Logitech, APC, Epson',
                'model_placeholder' => 'e.g. K120, BX1100LI, EB-X06',
                'serial_label' => 'Serial Number',
                'serial_placeholder' => 'e.g. Device serial number',
                'location_placeholder' => 'e.g. Finance Desk 01, Server Room',
                'notes_label' => 'Condition / Compatibility Notes',
                'notes_placeholder' => 'e.g. Battery health, paired device, compatibility, or known issues...',
                'specs_label' => 'Peripheral Specifications',
                'specs_placeholder' => 'Connection: USB | Interface: Wireless | Compatibility: Windows',
                'show_ip' => false,
                'show_specs' => true,
                'show_computer' => false,
            ],
            'software' => [
                'create_title' => 'Add Software License',
                'edit_title' => 'Edit Software License',
                'description' => 'Record software ownership, license key, seats, and expiry.',
                'name_placeholder' => 'e.g. Microsoft 365 Business Standard',
                'sub_label' => 'License Type',
                'sub_placeholder' => 'e.g. Subscription, Perpetual, OEM',
                'detail_heading' => 'License Details',
                'brand_placeholder' => 'e.g. Microsoft, Adobe, Autodesk',
                'model_placeholder' => 'e.g. Business Standard, Acrobat Pro',
                'serial_label' => 'Product / License Key',
                'serial_placeholder' => 'e.g. Product key or subscription ID',
                'location_placeholder' => 'e.g. Company-wide, Finance Department',
                'notes_label' => 'License / Renewal Notes',
                'notes_placeholder' => 'e.g. Renewal owner, billing cycle, or activation notes...',
                'specs_label' => 'License Specifications',
                'specs_placeholder' => 'Seats: 25 | Version: 2024 | Platform: Windows',
                'show_ip' => false,
                'show_specs' => true,
                'show_computer' => false,
            ],
            'other' => [
                'create_title' => 'Add Manual Asset',
                'edit_title' => 'Edit Manual Asset',
                'description' => 'Manually record and manage a non-agent device or hardware accessory.',
                'name_placeholder' => 'e.g. Meeting Room Device',
                'sub_label' => 'Sub Category',
                'sub_placeholder' => 'e.g. Equipment Type',
                'detail_heading' => 'Technical Details',
                'brand_placeholder' => 'e.g. Brand or vendor',
                'model_placeholder' => 'e.g. Model number',
                'serial_label' => 'Serial Number',
                'serial_placeholder' => 'e.g. S/N or Service Tag',
                'location_placeholder' => 'e.g. Server Room, Finance Room',
                'notes_label' => 'Technical Notes / Specs',
                'notes_placeholder' => 'Enter physical condition, maintenance, or assignment notes...',
                'specs_label' => 'Specifications',
                'specs_placeholder' => 'e.g. Connection: USB | Capacity: 1000 VA',
                'show_ip' => false,
                'show_specs' => true,
                'show_computer' => false,
            ],
        ];
    }
}
