<?php

namespace BusinessCore\Contracts;

use BusinessCore\Models\Business;

/**
 * Extension point for a future READ-ONLY bank feed supplied by a licensed
 * account-information provider. Nothing in this package implements it: the
 * shipped product only reads statement files the customer uploads.
 *
 * An implementation must never initiate a payment and must never see or store
 * the customer's online-banking credentials.
 */
interface BankConnector
{
    /** Is this business connected, with consent that has not expired? */
    public function isConnected(Business $business): bool;

    /**
     * Fetch settled transactions as a statement file on local disk, ready for
     * the normal StatementImporter. Returns the file path, or null if nothing new.
     */
    public function fetchStatementFile(Business $business): ?string;
}
