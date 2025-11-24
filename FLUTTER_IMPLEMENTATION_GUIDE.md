# 📱 Guide Complet Flutter - Système UE Vacataires

## 🎯 Vue d'ensemble

Ce guide contient **TOUT** ce dont tu as besoin pour implémenter le système UE dans l'app Flutter :
- Models Dart complets
- Services API
- Écrans UI avec code complet
- Gestion d'état (Provider/Riverpod/Bloc)
- Exemples de widgets

---

## 📂 Structure du projet

```
lib/
├── models/
│   ├── unite_enseignement.dart
│   ├── ue_statistics.dart
│   └── check_in_response.dart
├── services/
│   └── ue_service.dart
├── providers/
│   └── ue_provider.dart
├── screens/
│   ├── mes_ue_screen.dart
│   └── check_in_ue_screen.dart
└── widgets/
    ├── ue_card_widget.dart
    └── ue_progress_bar.dart
```

---

## 1️⃣ Models Dart

### `lib/models/unite_enseignement.dart`

```dart
class UniteEnseignement {
  final int id;
  final String? codeUe;
  final String nomMatiere;
  final double volumeHoraireTotal;
  final double heuresEffectuees;
  final double heuresRestantes;
  final double pourcentageProgression;
  final double montantPaye;
  final double montantRestant;
  final double montantMax;
  final double tauxHoraire;
  final String statut; // 'activee' ou 'non_activee'
  final String? anneeAcademique;
  final int? semestre;
  final DateTime? dateActivation;
  final DateTime? dateAttribution;

  UniteEnseignement({
    required this.id,
    this.codeUe,
    required this.nomMatiere,
    required this.volumeHoraireTotal,
    required this.heuresEffectuees,
    required this.heuresRestantes,
    required this.pourcentageProgression,
    required this.montantPaye,
    required this.montantRestant,
    required this.montantMax,
    required this.tauxHoraire,
    required this.statut,
    this.anneeAcademique,
    this.semestre,
    this.dateActivation,
    this.dateAttribution,
  });

  factory UniteEnseignement.fromJson(Map<String, dynamic> json) {
    return UniteEnseignement(
      id: json['id'],
      codeUe: json['code_ue'],
      nomMatiere: json['nom_matiere'],
      volumeHoraireTotal: (json['volume_horaire_total'] as num).toDouble(),
      heuresEffectuees: (json['heures_effectuees'] as num).toDouble(),
      heuresRestantes: (json['heures_restantes'] as num).toDouble(),
      pourcentageProgression: (json['pourcentage_progression'] as num).toDouble(),
      montantPaye: (json['montant_paye'] as num).toDouble(),
      montantRestant: (json['montant_restant'] as num).toDouble(),
      montantMax: (json['montant_max'] as num).toDouble(),
      tauxHoraire: (json['taux_horaire'] as num).toDouble(),
      statut: json['statut'],
      anneeAcademique: json['annee_academique'],
      semestre: json['semestre'],
      dateActivation: json['date_activation'] != null
          ? DateTime.parse(json['date_activation'])
          : null,
      dateAttribution: json['date_attribution'] != null
          ? DateTime.parse(json['date_attribution'])
          : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'code_ue': codeUe,
      'nom_matiere': nomMatiere,
      'volume_horaire_total': volumeHoraireTotal,
      'heures_effectuees': heuresEffectuees,
      'heures_restantes': heuresRestantes,
      'pourcentage_progression': pourcentageProgression,
      'montant_paye': montantPaye,
      'montant_restant': montantRestant,
      'montant_max': montantMax,
      'taux_horaire': tauxHoraire,
      'statut': statut,
      'annee_academique': anneeAcademique,
      'semestre': semestre,
    };
  }

  bool get isActivee => statut == 'activee';
  bool get hasHoursRemaining => heuresRestantes > 0;
}
```

### `lib/models/ue_statistics.dart`

```dart
class UeStatistics {
  final int nombreUeActivees;
  final double volumeHoraireTotal;
  final double heuresEffectuees;
  final double heuresRestantes;
  final double pourcentageGlobal;
  final double montantPaye;
  final double montantPotentielMax;
  final double montantRestant;
  final double tauxHoraire;

  UeStatistics({
    required this.nombreUeActivees,
    required this.volumeHoraireTotal,
    required this.heuresEffectuees,
    required this.heuresRestantes,
    required this.pourcentageGlobal,
    required this.montantPaye,
    required this.montantPotentielMax,
    required this.montantRestant,
    required this.tauxHoraire,
  });

  factory UeStatistics.fromJson(Map<String, dynamic> json) {
    return UeStatistics(
      nombreUeActivees: json['nombre_ue_activees'],
      volumeHoraireTotal: (json['volume_horaire_total'] as num).toDouble(),
      heuresEffectuees: (json['heures_effectuees'] as num).toDouble(),
      heuresRestantes: (json['heures_restantes'] as num).toDouble(),
      pourcentageGlobal: (json['pourcentage_global'] as num).toDouble(),
      montantPaye: (json['montant_paye'] as num).toDouble(),
      montantPotentielMax: (json['montant_potentiel_max'] as num).toDouble(),
      montantRestant: (json['montant_restant'] as num).toDouble(),
      tauxHoraire: (json['taux_horaire'] as num).toDouble(),
    );
  }
}
```

---

## 2️⃣ Service API

### `lib/services/ue_service.dart`

```dart
import 'dart:convert';
import 'package:http/http.dart' as http;
import '../models/unite_enseignement.dart';
import '../models/ue_statistics.dart';

class UeService {
  final String baseUrl;
  final String token;

  UeService({required this.baseUrl, required this.token});

  Map<String, String> get _headers => {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $token',
      };

  /// Liste complète des UE (activées + non activées)
  Future<Map<String, dynamic>> getMesUE() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/api/unites-enseignement'),
        headers: _headers,
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);

        return {
          'success': data['success'],
          'unites_activees': (data['data']['unites_activees'] as List)
              .map((ue) => UniteEnseignement.fromJson(ue))
              .toList(),
          'unites_non_activees': (data['data']['unites_non_activees'] as List)
              .map((ue) => UniteEnseignement.fromJson(ue))
              .toList(),
          'totaux': data['data']['totaux'],
        };
      } else {
        throw Exception('Erreur lors du chargement des UE');
      }
    } catch (e) {
      throw Exception('Erreur réseau: $e');
    }
  }

  /// UE activées disponibles pour check-in
  Future<List<UniteEnseignement>> getUEActives() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/api/unites-enseignement/actives'),
        headers: _headers,
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        return (data['data'] as List)
            .map((ue) => UniteEnseignement.fromJson(ue))
            .toList();
      } else {
        throw Exception('Erreur lors du chargement des UE actives');
      }
    } catch (e) {
      throw Exception('Erreur réseau: $e');
    }
  }

  /// Détails d'une UE spécifique
  Future<UniteEnseignement> getUEDetails(int ueId) async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/api/unites-enseignement/$ueId'),
        headers: _headers,
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        return UniteEnseignement.fromJson(data['data']);
      } else {
        throw Exception('UE introuvable');
      }
    } catch (e) {
      throw Exception('Erreur réseau: $e');
    }
  }

  /// Statistiques globales
  Future<UeStatistics> getStatistiques() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/api/unites-enseignement/statistiques'),
        headers: _headers,
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        return UeStatistics.fromJson(data['data']);
      } else {
        throw Exception('Erreur lors du chargement des statistiques');
      }
    } catch (e) {
      throw Exception('Erreur réseau: $e');
    }
  }

  /// Check-in avec UE sélectionnée
  Future<Map<String, dynamic>> checkInWithUE({
    required int campusId,
    required double latitude,
    required double longitude,
    required int uniteEnseignementId,
    double? accuracy,
  }) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/api/attendance/check-in'),
        headers: _headers,
        body: json.encode({
          'campus_id': campusId,
          'latitude': latitude,
          'longitude': longitude,
          'accuracy': accuracy,
          'unite_enseignement_id': uniteEnseignementId,
        }),
      );

      final data = json.decode(response.body);

      if (response.statusCode == 201) {
        return {
          'success': true,
          'message': data['message'],
          'attendance': data['attendance'],
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Erreur lors du check-in',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Erreur réseau: $e',
      };
    }
  }
}
```

---

## 3️⃣ Provider (avec Riverpod)

### `lib/providers/ue_provider.dart`

```dart
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/unite_enseignement.dart';
import '../services/ue_service.dart';

// State pour les UE
class UeState {
  final List<UniteEnseignement> unitesActivees;
  final List<UniteEnseignement> unitesNonActivees;
  final Map<String, dynamic>? totaux;
  final bool isLoading;
  final String? error;

  UeState({
    this.unitesActivees = const [],
    this.unitesNonActivees = const [],
    this.totaux,
    this.isLoading = false,
    this.error,
  });

  UeState copyWith({
    List<UniteEnseignement>? unitesActivees,
    List<UniteEnseignement>? unitesNonActivees,
    Map<String, dynamic>? totaux,
    bool? isLoading,
    String? error,
  }) {
    return UeState(
      unitesActivees: unitesActivees ?? this.unitesActivees,
      unitesNonActivees: unitesNonActivees ?? this.unitesNonActivees,
      totaux: totaux ?? this.totaux,
      isLoading: isLoading ?? this.isLoading,
      error: error,
    );
  }
}

// Provider
class UeNotifier extends StateNotifier<UeState> {
  final UeService _ueService;

  UeNotifier(this._ueService) : super(UeState());

  Future<void> loadUE() async {
    state = state.copyWith(isLoading: true, error: null);

    try {
      final result = await _ueService.getMesUE();

      state = state.copyWith(
        unitesActivees: result['unites_activees'],
        unitesNonActivees: result['unites_non_activees'],
        totaux: result['totaux'],
        isLoading: false,
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        error: e.toString(),
      );
    }
  }

  Future<List<UniteEnseignement>> loadUEActives() async {
    try {
      return await _ueService.getUEActives();
    } catch (e) {
      state = state.copyWith(error: e.toString());
      return [];
    }
  }
}

// Service provider
final ueServiceProvider = Provider<UeService>((ref) {
  // TODO: Get baseUrl and token from auth provider
  return UeService(
    baseUrl: 'http://your-api-url.com',
    token: 'your-auth-token',
  );
});

// UE provider
final ueProvider = StateNotifierProvider<UeNotifier, UeState>((ref) {
  final service = ref.watch(ueServiceProvider);
  return UeNotifier(service);
});
```

---

## 4️⃣ Écrans UI

### `lib/screens/mes_ue_screen.dart`

```dart
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../providers/ue_provider.dart';
import '../widgets/ue_card_widget.dart';
import 'package:intl/intl.dart';

class MesUEScreen extends ConsumerStatefulWidget {
  @override
  _MesUEScreenState createState() => _MesUEScreenState();
}

class _MesUEScreenState extends ConsumerState<MesUEScreen> {
  final currencyFormatter = NumberFormat('#,###', 'fr_FR');

  @override
  void initState() {
    super.initState();
    // Charger les UE au démarrage
    Future.microtask(() => ref.read(ueProvider.notifier).loadUE());
  }

  @override
  Widget build(BuildContext context) {
    final ueState = ref.watch(ueProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text('Mes Unités d\'Enseignement'),
        backgroundColor: Colors.indigo,
        elevation: 0,
      ),
      body: ueState.isLoading
          ? Center(child: CircularProgressIndicator())
          : ueState.error != null
              ? _buildErrorState(ueState.error!)
              : _buildContent(ueState),
    );
  }

  Widget _buildErrorState(String error) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.error_outline, size: 64, color: Colors.red),
          SizedBox(height: 16),
          Text('Erreur: $error'),
          SizedBox(height: 16),
          ElevatedButton(
            onPressed: () => ref.read(ueProvider.notifier).loadUE(),
            child: Text('Réessayer'),
          ),
        ],
      ),
    );
  }

  Widget _buildContent(UeState state) {
    return RefreshIndicator(
      onRefresh: () => ref.read(ueProvider.notifier).loadUE(),
      child: ListView(
        padding: EdgeInsets.all(16),
        children: [
          // Statistiques en haut
          if (state.totaux != null) _buildStatistics(state.totaux!),
          SizedBox(height: 24),

          // UE ACTIVÉES
          _buildSectionHeader(
            'UE Activées',
            Icons.check_circle,
            Colors.green,
            state.unitesActivees.length,
          ),
          SizedBox(height: 12),
          if (state.unitesActivees.isEmpty)
            _buildEmptyState('Aucune UE activée')
          else
            ...state.unitesActivees.map((ue) => UeCardWidget(
                  ue: ue,
                  isActive: true,
                )),

          SizedBox(height: 32),

          // UE NON ACTIVÉES
          _buildSectionHeader(
            'En Attente d\'Activation',
            Icons.hourglass_empty,
            Colors.orange,
            state.unitesNonActivees.length,
          ),
          SizedBox(height: 12),
          if (state.unitesNonActivees.isEmpty)
            _buildEmptyState('Toutes vos UE sont activées')
          else
            ...state.unitesNonActivees.map((ue) => UeCardWidget(
                  ue: ue,
                  isActive: false,
                )),
        ],
      ),
    );
  }

  Widget _buildStatistics(Map<String, dynamic> totaux) {
    return Card(
      elevation: 4,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: Container(
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(12),
          gradient: LinearGradient(
            colors: [Colors.indigo, Colors.purple],
          ),
        ),
        padding: EdgeInsets.all(20),
        child: Column(
          children: [
            Text(
              'Récapitulatif Global',
              style: TextStyle(
                color: Colors.white,
                fontSize: 18,
                fontWeight: FontWeight.bold,
              ),
            ),
            SizedBox(height: 20),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceAround,
              children: [
                _buildStatItem(
                  'Heures Effectuées',
                  '${totaux['heures_effectuees']}h',
                  Icons.access_time,
                ),
                _buildStatItem(
                  'Montant Gagné',
                  '${currencyFormatter.format(totaux['montant_paye'])} F',
                  Icons.money,
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildStatItem(String label, String value, IconData icon) {
    return Column(
      children: [
        Icon(icon, color: Colors.white70, size: 28),
        SizedBox(height: 8),
        Text(
          value,
          style: TextStyle(
            color: Colors.white,
            fontSize: 20,
            fontWeight: FontWeight.bold,
          ),
        ),
        Text(
          label,
          style: TextStyle(
            color: Colors.white70,
            fontSize: 12,
          ),
        ),
      ],
    );
  }

  Widget _buildSectionHeader(
      String title, IconData icon, Color color, int count) {
    return Row(
      children: [
        Icon(icon, color: color, size: 24),
        SizedBox(width: 8),
        Text(
          title,
          style: TextStyle(
            fontSize: 18,
            fontWeight: FontWeight.bold,
            color: Colors.black87,
          ),
        ),
        SizedBox(width: 8),
        Container(
          padding: EdgeInsets.symmetric(horizontal: 8, vertical: 4),
          decoration: BoxDecoration(
            color: color.withOpacity(0.1),
            borderRadius: BorderRadius.circular(12),
          ),
          child: Text(
            '$count',
            style: TextStyle(
              color: color,
              fontWeight: FontWeight.bold,
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildEmptyState(String message) {
    return Card(
      child: Padding(
        padding: EdgeInsets.all(32),
        child: Center(
          child: Column(
            children: [
              Icon(Icons.inbox, size: 48, color: Colors.grey),
              SizedBox(height: 12),
              Text(
                message,
                style: TextStyle(color: Colors.grey),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
```

---

## 5️⃣ Widgets

### `lib/widgets/ue_card_widget.dart`

```dart
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../models/unite_enseignement.dart';

class UeCardWidget extends StatelessWidget {
  final UniteEnseignement ue;
  final bool isActive;

  const UeCardWidget({
    Key? key,
    required this.ue,
    required this.isActive,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    final currencyFormatter = NumberFormat('#,###', 'fr_FR');

    return Card(
      margin: EdgeInsets.only(bottom: 12),
      elevation: 2,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: Container(
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: isActive ? Colors.green.shade200 : Colors.orange.shade200,
            width: 2,
          ),
        ),
        child: Padding(
          padding: EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // En-tête
              Row(
                children: [
                  Icon(
                    Icons.book,
                    color: isActive ? Colors.green : Colors.orange,
                    size: 28,
                  ),
                  SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          ue.nomMatiere,
                          style: TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        if (ue.codeUe != null)
                          Text(
                            ue.codeUe!,
                            style: TextStyle(
                              color: Colors.grey,
                              fontSize: 12,
                            ),
                          ),
                      ],
                    ),
                  ),
                  _buildStatusBadge(),
                ],
              ),

              SizedBox(height: 16),

              // Infos pour UE activée
              if (isActive) ...[
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    _buildInfoColumn(
                      'Volume',
                      '${ue.volumeHoraireTotal}h',
                      Colors.blue,
                    ),
                    _buildInfoColumn(
                      'Effectué',
                      '${ue.heuresEffectuees}h',
                      Colors.green,
                    ),
                    _buildInfoColumn(
                      'Reste',
                      '${ue.heuresRestantes}h',
                      Colors.orange,
                    ),
                  ],
                ),

                SizedBox(height: 16),

                // Barre de progression
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          'Progression',
                          style: TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                        Text(
                          '${ue.pourcentageProgression.toStringAsFixed(1)}%',
                          style: TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.bold,
                            color: Colors.indigo,
                          ),
                        ),
                      ],
                    ),
                    SizedBox(height: 8),
                    ClipRRect(
                      borderRadius: BorderRadius.circular(8),
                      child: LinearProgressIndicator(
                        value: ue.pourcentageProgression / 100,
                        minHeight: 8,
                        backgroundColor: Colors.grey.shade200,
                        valueColor: AlwaysStoppedAnimation<Color>(
                          _getProgressColor(ue.pourcentageProgression),
                        ),
                      ),
                    ),
                  ],
                ),

                SizedBox(height: 16),

                // Montants
                Container(
                  padding: EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: Colors.green.shade50,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Gagné',
                            style: TextStyle(
                              fontSize: 11,
                              color: Colors.grey.shade600,
                            ),
                          ),
                          Text(
                            '${currencyFormatter.format(ue.montantPaye)} F',
                            style: TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.bold,
                              color: Colors.green.shade700,
                            ),
                          ),
                        ],
                      ),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.end,
                        children: [
                          Text(
                            'Potentiel restant',
                            style: TextStyle(
                              fontSize: 11,
                              color: Colors.grey.shade600,
                            ),
                          ),
                          Text(
                            '${currencyFormatter.format(ue.montantRestant)} F',
                            style: TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.bold,
                              color: Colors.orange.shade700,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ] else ...[
                // Infos pour UE non activée
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Volume horaire',
                          style: TextStyle(
                            fontSize: 12,
                            color: Colors.grey,
                          ),
                        ),
                        Text(
                          '${ue.volumeHoraireTotal}h',
                          style: TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ],
                    ),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        Text(
                          'Potentiel',
                          style: TextStyle(
                            fontSize: 12,
                            color: Colors.grey,
                          ),
                        ),
                        Text(
                          '${currencyFormatter.format(ue.montantMax)} F',
                          style: TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                            color: Colors.purple,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildStatusBadge() {
    return Container(
      padding: EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      decoration: BoxDecoration(
        color: isActive ? Colors.green.shade100 : Colors.orange.shade100,
        borderRadius: BorderRadius.circular(20),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(
            isActive ? Icons.check_circle : Icons.hourglass_empty,
            size: 16,
            color: isActive ? Colors.green.shade700 : Colors.orange.shade700,
          ),
          SizedBox(width: 4),
          Text(
            isActive ? 'Active' : 'En attente',
            style: TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.bold,
              color: isActive ? Colors.green.shade700 : Colors.orange.shade700,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildInfoColumn(String label, String value, Color color) {
    return Column(
      children: [
        Text(
          label,
          style: TextStyle(
            fontSize: 11,
            color: Colors.grey.shade600,
          ),
        ),
        SizedBox(height: 4),
        Text(
          value,
          style: TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.bold,
            color: color,
          ),
        ),
      ],
    );
  }

  Color _getProgressColor(double percentage) {
    if (percentage < 33) return Colors.red;
    if (percentage < 66) return Colors.orange;
    return Colors.green;
  }
}
```

---

## 6️⃣ Check-in avec sélection UE

### `lib/screens/check_in_ue_screen.dart`

```dart
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/unite_enseignement.dart';
import '../providers/ue_provider.dart';

class CheckInUEScreen extends ConsumerStatefulWidget {
  final int campusId;
  final double latitude;
  final double longitude;

  const CheckInUEScreen({
    required this.campusId,
    required this.latitude,
    required this.longitude,
  });

  @override
  _CheckInUEScreenState createState() => _CheckInUEScreenState();
}

class _CheckInUEScreenState extends ConsumerState<CheckInUEScreen> {
  UniteEnseignement? selectedUE;
  bool isLoading = false;
  List<UniteEnseignement> ueActives = [];

  @override
  void initState() {
    super.initState();
    _loadUEActives();
  }

  Future<void> _loadUEActives() async {
    setState(() => isLoading = true);

    try {
      final ues = await ref.read(ueProvider.notifier).loadUEActives();
      setState(() {
        ueActives = ues;
        isLoading = false;
      });
    } catch (e) {
      setState(() => isLoading = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Erreur: $e')),
      );
    }
  }

  Future<void> _performCheckIn() async {
    if (selectedUE == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Veuillez sélectionner une matière')),
      );
      return;
    }

    setState(() => isLoading = true);

    try {
      final service = ref.read(ueServiceProvider);
      final result = await service.checkInWithUE(
        campusId: widget.campusId,
        latitude: widget.latitude,
        longitude: widget.longitude,
        uniteEnseignementId: selectedUE!.id,
      );

      if (result['success']) {
        Navigator.pop(context, true);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(result['message']),
            backgroundColor: Colors.green,
          ),
        );
      } else {
        setState(() => isLoading = false);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(result['message']),
            backgroundColor: Colors.red,
          ),
        );
      }
    } catch (e) {
      setState(() => isLoading = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Erreur: $e')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Sélectionner la matière'),
        backgroundColor: Colors.indigo,
      ),
      body: isLoading
          ? Center(child: CircularProgressIndicator())
          : ueActives.isEmpty
              ? _buildEmptyState()
              : _buildContent(),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.inbox, size: 64, color: Colors.grey),
          SizedBox(height: 16),
          Text(
            'Aucune matière disponible',
            style: TextStyle(fontSize: 16, color: Colors.grey),
          ),
          SizedBox(height: 8),
          Text(
            'Contactez l\'administration',
            style: TextStyle(fontSize: 14, color: Colors.grey.shade600),
          ),
        ],
      ),
    );
  }

  Widget _buildContent() {
    return Column(
      children: [
        Container(
          padding: EdgeInsets.all(16),
          color: Colors.blue.shade50,
          child: Row(
            children: [
              Icon(Icons.info_outline, color: Colors.blue),
              SizedBox(width: 12),
              Expanded(
                child: Text(
                  'Sélectionnez la matière que vous allez enseigner',
                  style: TextStyle(color: Colors.blue.shade800),
                ),
              ),
            ],
          ),
        ),
        Expanded(
          child: ListView.builder(
            padding: EdgeInsets.all(16),
            itemCount: ueActives.length,
            itemBuilder: (context, index) {
              final ue = ueActives[index];
              final isSelected = selectedUE?.id == ue.id;

              return Card(
                margin: EdgeInsets.only(bottom: 12),
                elevation: isSelected ? 4 : 1,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                  side: BorderSide(
                    color: isSelected ? Colors.indigo : Colors.transparent,
                    width: 2,
                  ),
                ),
                child: InkWell(
                  onTap: () => setState(() => selectedUE = ue),
                  borderRadius: BorderRadius.circular(12),
                  child: Padding(
                    padding: EdgeInsets.all(16),
                    child: Row(
                      children: [
                        Radio<int>(
                          value: ue.id,
                          groupValue: selectedUE?.id,
                          onChanged: (value) => setState(() => selectedUE = ue),
                          activeColor: Colors.indigo,
                        ),
                        SizedBox(width: 12),
                        Icon(
                          Icons.book,
                          color: isSelected ? Colors.indigo : Colors.grey,
                          size: 32,
                        ),
                        SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                ue.nomMatiere,
                                style: TextStyle(
                                  fontSize: 16,
                                  fontWeight: FontWeight.bold,
                                  color: isSelected ? Colors.indigo : Colors.black87,
                                ),
                              ),
                              SizedBox(height: 4),
                              Text(
                                'Reste: ${ue.heuresRestantes}h / ${ue.volumeHoraireTotal}h',
                                style: TextStyle(
                                  fontSize: 12,
                                  color: Colors.grey.shade600,
                                ),
                              ),
                              SizedBox(height: 4),
                              ClipRRect(
                                borderRadius: BorderRadius.circular(4),
                                child: LinearProgressIndicator(
                                  value: ue.pourcentageProgression / 100,
                                  minHeight: 4,
                                  backgroundColor: Colors.grey.shade200,
                                  valueColor: AlwaysStoppedAnimation<Color>(
                                    isSelected ? Colors.indigo : Colors.grey,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              );
            },
          ),
        ),
        Container(
          padding: EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: Colors.white,
            boxShadow: [
              BoxShadow(
                color: Colors.black12,
                blurRadius: 4,
                offset: Offset(0, -2),
              ),
            ],
          ),
          child: SafeArea(
            child: ElevatedButton(
              onPressed: selectedUE != null && !isLoading ? _performCheckIn : null,
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.indigo,
                padding: EdgeInsets.symmetric(vertical: 16),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
              child: isLoading
                  ? SizedBox(
                      height: 20,
                      width: 20,
                      child: CircularProgressIndicator(color: Colors.white),
                    )
                  : Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(Icons.check_circle, size: 24),
                        SizedBox(width: 8),
                        Text(
                          'Confirmer le check-in',
                          style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                        ),
                      ],
                    ),
            ),
          ),
        ),
      ],
    );
  }
}
```

---

## 7️⃣ Navigation & intégration

### Comment intégrer dans votre workflow de check-in

```dart
// Dans votre CheckInScreen actuel, pour les vacataires uniquement

Future<void> _handleCheckIn() async {
  // ... code existant (vérif localisation, etc.)

  // Si l'utilisateur est un enseignant vacataire
  if (user.employeeType == 'enseignant_vacataire') {
    // Afficher l'écran de sélection UE
    final result = await Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => CheckInUEScreen(
          campusId: selectedCampus.id,
          latitude: currentLatitude,
          longitude: currentLongitude,
        ),
      ),
    );

    if (result == true) {
      // Check-in réussi
      // Rafraîchir les données, naviguer, etc.
    }
  } else {
    // Check-in normal pour les autres types d'employés
    await _normalCheckIn();
  }
}
```

---

## 📱 Bottom Navigation avec onglet UE

```dart
// Ajouter un onglet dans votre BottomNavigationBar
// Uniquement pour les vacataires

List<BottomNavigationBarItem> _getNavItems() {
  final items = [
    BottomNavigationBarItem(
      icon: Icon(Icons.home),
      label: 'Accueil',
    ),
    BottomNavigationBarItem(
      icon: Icon(Icons.history),
      label: 'Historique',
    ),
    BottomNavigationBarItem(
      icon: Icon(Icons.person),
      label: 'Profil',
    ),
  ];

  // Ajouter l'onglet UE pour les vacataires
  if (user.employeeType == 'enseignant_vacataire') {
    items.insert(
      2,
      BottomNavigationBarItem(
        icon: Icon(Icons.book),
        label: 'Mes UE',
      ),
    );
  }

  return items;
}
```

---

## 🎨 Personnalisation & améliorations

### Animations

```dart
// Anim pagination lors de la progression
AnimatedContainer(
  duration: Duration(milliseconds: 300),
  width: MediaQuery.of(context).size.width * (ue.pourcentageProgression / 100),
  // ...
)
```

### Notifications locales

```dart
// Rappel quand il reste peu d'heures
if (ue.heuresRestantes < 5) {
  showLocalNotification(
    title: 'Attention',
    body: 'Il ne reste que ${ue.heuresRestantes}h pour ${ue.nomMatiere}',
  );
}
```

---

## ✅ Checklist d'implémentation

- [ ] Copier les models dans `lib/models/`
- [ ] Copier le service dans `lib/services/`
- [ ] Configurer le provider (Riverpod/Provider/Bloc)
- [ ] Créer l'écran "Mes UE"
- [ ] Créer l'écran de sélection UE au check-in
- [ ] Modifier le flux de check-in pour les vacataires
- [ ] Ajouter l'onglet UE au bottom nav (vacataires only)
- [ ] Tester avec un compte vacataire
- [ ] Gérer les erreurs réseau
- [ ] Ajouter pull-to-refresh

---

## 🐛 Gestion des erreurs

```dart
// Toujours wrapper les appels API
try {
  final ues = await ueService.getMesUE();
  // ...
} on SocketException {
  // Pas de connexion
  showError('Pas de connexion Internet');
} on TimeoutException {
  // Timeout
  showError('La requête a expiré');
} catch (e) {
  // Autre erreur
  showError('Une erreur est survenue: $e');
}
```

---

## 📞 Support

Tout le code est prêt à l'emploi. Si besoin :
- API doc: `API_DOCUMENTATION_UE.md`
- Tests backend: `TEST_QUICK_UE.md`

**Date** : 22 novembre 2024
**Version** : 1.0.0
