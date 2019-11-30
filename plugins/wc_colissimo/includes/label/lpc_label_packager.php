<?php

class LpcLabelPackager extends LpcComponent {

	/** @var LpcLabelDb */
	protected $labelDb;
	/** @var LpcInvoiceGenerateAction */
	protected $invoiceGenerateAction;

	public function __construct(LpcLabelDb $labelDb = null) {
		$this->labelDb               = LpcRegister::get('labelDb', $labelDb);
		$this->invoiceGenerateAction = LpcRegister::get('invoiceGenerateAction');
	}

	public function getDependencies() {
		return ['labelDb', 'invoiceGenerateAction'];
	}

	public function generateZip(array $orderIds) {
		$zip      = new ZipArchive();
		$filename = tempnam(sys_get_temp_dir(), 'colissimo.');
		try {
			$zip->open($filename, ZipArchive::CREATE);

			foreach ($orderIds as $id) {
				$zipDirname = $id;
				$zip->addEmptyDir($zipDirname);

				$outwardLabel = $this->labelDb->getOutwardLabelFor($id);
				if (!empty($outwardLabel)) {
					$zip->addFromString($zipDirname . '/outward_label.pdf', $outwardLabel);
					$this->invoiceGenerateAction->generateInvoice($id, sys_get_temp_dir() . DS . 'invoice.pdf', MergePdf::DESTINATION__DISK);
					$zip->addFile(sys_get_temp_dir() . DS . 'invoice.pdf', $zipDirname . '/invoice.pdf');
				}

				$outwardCn23 = $this->labelDb->getOutwardCn23For($id);
				if (!empty($outwardCn23)) {
					$zip->addFromString($zipDirname . '/outward_cn23.pdf', $outwardCn23);
				}

				$inwardLabel = $this->labelDb->getInwardLabelFor($id);
				if (!empty($inwardLabel)) {
					$zip->addFromString($zipDirname . '/inward_label.pdf', $inwardLabel);
				}

				$inwardCn23 = $this->labelDb->getInwardCn23For($id);
				if (!empty($inwardCn23)) {
					$zip->addFromString($zipDirname . '/inward_cn23.pdf', $inwardCn23);
				}
			}

			$zip->close();

			$content = readfile($filename);

			return $content;
		} finally {
			unlink($filename);
		}
	}
}
